import type { StyleSpecification } from 'maplibre-gl';

export type MapMode = 'clear' | 'streets' | 'dark' | 'municipal';
export type BoundaryLevel = 'province' | 'city' | 'barangay';
export const mapModes: { id: MapMode; label: string; description: string }[] = [
  { id: 'clear', label: 'Clear', description: 'Fewer details, clearer hazards' },
  { id: 'streets', label: 'Streets', description: 'Detailed roads and landmarks' },
  { id: 'dark', label: 'Dark', description: 'A darker map background' },
  ...(import.meta.env.VITE_MAP_STYLE_URL ? [{ id: 'municipal' as const, label: 'Municipal', description: 'The municipality’s configured map' }] : []),
];
export const boundaryLevels: { id: BoundaryLevel; label: string; adminLevel: number; minZoom: number; color: string; darkColor: string; url?: string }[] = [
  { id: 'province', label: 'Provinces', adminLevel: 4, minZoom: 5, color: '#7850a0', darkColor: '#d2b3f1', url: import.meta.env.VITE_PROVINCE_GEOJSON_URL || `${import.meta.env.BASE_URL}boundaries/pangasinan-province.geojson` },
  { id: 'city', label: 'Cities / municipalities', adminLevel: 6, minZoom: 9, color: '#196f65', darkColor: '#78d6c7', url: import.meta.env.VITE_CITY_GEOJSON_URL || `${import.meta.env.BASE_URL}boundaries/pangasinan-cities-municipalities.geojson` },
  { id: 'barangay', label: 'Barangays', adminLevel: 10, minZoom: 12, color: '#a65313', darkColor: '#ffc17c', url: import.meta.env.VITE_BARANGAY_GEOJSON_URL || `${import.meta.env.BASE_URL}boundaries/basista-barangays.geojson` },
];
export const referenceBoundarySource = 'munialert-reference-boundaries';
export const referenceBoundaryUrl = 'https://tiles.openfreemap.org/planet';
export const boundaryAttribution = '<a href="https://openfreemap.org/">OpenFreeMap</a> © <a href="https://openmaptiles.org/">OpenMapTiles</a> Data from <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';

export function mapStyle(mode: MapMode): string | StyleSpecification {
  if (mode === 'municipal' && import.meta.env.VITE_MAP_STYLE_URL) return import.meta.env.VITE_MAP_STYLE_URL;
  if (mode !== 'streets') return `https://tiles.openfreemap.org/styles/${mode === 'dark' ? 'dark' : 'positron'}`;
  return {
    version: 8, glyphs: 'https://tiles.openfreemap.org/fonts/{fontstack}/{range}.pbf',
    sources: { osm: { type: 'raster', tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'], tileSize: 256, maxzoom: 19, attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors' } },
    layers: [{ id: 'osm', type: 'raster', source: 'osm' }],
  };
}
