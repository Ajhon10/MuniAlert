import type { Map, FilterSpecification, VisibilitySpecification } from 'maplibre-gl';
import { boundaryLevels, referenceBoundarySource, referenceBoundaryUrl, boundaryAttribution } from '../config/maps';
import { mapPreferences } from './mapPreferences';
const originalLabels = new WeakMap<Map, Record<string, VisibilitySpecification>>();

export function updateBoundaryLayers(map: Map, newStyle = false) {
  const style = map.getStyle();
  if (!style?.sources) return;
  if (newStyle || !originalLabels.has(map)) originalLabels.set(map, {});
  const labels = originalLabels.get(map)!;
  // Replace the provider's faint administrative lines with the explicit switches.
  for (const layer of style.layers || []) {
    if (!layer.id.startsWith('munialert-') && 'source-layer' in layer && layer['source-layer'] === 'boundary') map.setLayoutProperty(layer.id, 'visibility', 'none');
    if (!layer.id.startsWith('munialert-') && layer.type === 'symbol' && layer['source-layer'] === 'place') {
      labels[layer.id] ??= layer.layout?.visibility || 'visible';
      map.setLayoutProperty(layer.id, 'visibility', mapPreferences.value.boundaries.barangay && map.getZoom() >= 12 ? 'none' : labels[layer.id]);
    }
  }
  const existingVector = Object.entries(style.sources).find(([id, source]) => id !== referenceBoundarySource && source.type === 'vector' && source.url === referenceBoundaryUrl)?.[0];
  for (const level of boundaryLevels) {
    const enabled = mapPreferences.value.boundaries[level.id];
    const sourceId = level.url ? `munialert-${level.id}-data` : existingVector || referenceBoundarySource;
    const id = `munialert-${level.id}-boundary`;
    if (enabled && !map.getSource(sourceId)) map.addSource(sourceId, level.url ? { type: 'geojson', data: level.url, attribution: 'Reference boundaries: PSA / NAMRIA · altcoder / Manuel Balmeo' } : { type: 'vector', url: referenceBoundaryUrl, attribution: boundaryAttribution });
    if (enabled && !map.getLayer(id)) {
      const filter: FilterSpecification = ['all', ['==', ['to-number', ['get', 'admin_level']], level.adminLevel], ['!=', ['get', 'maritime'], 1], ['!=', ['get', 'disputed'], 1]];
      map.addLayer({ id, source: sourceId, type: 'line', ...(!level.url ? { 'source-layer': 'boundary', filter } : {}), minzoom: level.minZoom,
        layout: { 'line-join': 'round', 'line-cap': 'round' },
        paint: { 'line-color': mapPreferences.value.mode === 'dark' ? level.darkColor : level.color, 'line-width': level.id === 'province' ? 3 : level.id === 'city' ? 2.5 : 2, ...(level.id === 'barangay' ? { 'line-dasharray': [3, 2] } : {}), 'line-opacity': 0.95 },
      });
      if (level.url && style.glyphs) map.addLayer({ id: `${id}-labels`, source: sourceId, type: 'symbol', minzoom: level.minZoom, layout: { 'text-field': ['coalesce', ['get', 'name'], ['get', 'NAME'], ''], 'text-font': ['Noto Sans Regular'], 'text-size': 13, 'text-max-width': 10 }, paint: { 'text-color': '#244c40', 'text-halo-color': '#ffffff', 'text-halo-width': 2 } });
    }
    for (const layerId of [id, `${id}-labels`]) if (map.getLayer(layerId)) map.setLayoutProperty(layerId, 'visibility', enabled ? 'visible' : 'none');
  }
}
