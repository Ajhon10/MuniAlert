"""Extract the public, pinned reference dataset; never ingest app incident data."""
import hashlib
import json
import zipfile
from pathlib import Path

root = Path(__file__).resolve().parent.parent
archive = root / '.cache/boundary-source.zip'
expected = 'cbf0a3f08c1284dd6d5ce385fd656e94e7e848c495bb5407a332d3da307a9415'
assert hashlib.sha256(archive.read_bytes()).hexdigest() == expected, 'Unexpected source archive'
destination = root / 'hazard-client/public/boundaries'
destination.mkdir(parents=True, exist_ok=True)
zipped = zipfile.ZipFile(archive)
names = ['Anambongan', 'Bayoyong', 'Cabeldatan', 'Dumpay', 'Malimpec East', 'Mapolopolo', 'Nalneran', 'Navatat', 'Obong', 'Osme\u00f1a Sr.', 'Palma', 'Patacbo', 'Poblacion']
outputs = []
for kind in ['provinces']:
    document = json.loads(zipped.read(kind + '.geojson'))
    if kind == 'barangays':
        selected = [feature for feature in document['features'] if feature['properties'].get('ADM3_PCODE') == 'PH0105509']
    elif kind == 'municipalities':
        selected = [feature for feature in document['features'] if feature['properties'].get('ADM2_PCODE') == 'PH01055']
        for city_kind in ['component_cities', 'highly_urbanized_cities', 'independent_component_cities']:
            selected += [feature for feature in json.loads(zipped.read(city_kind + '.geojson'))['features'] if feature['properties'].get('ADM2_PCODE') == 'PH01055']
    else:
        selected = [feature for feature in document['features'] if feature['properties'].get('ADM2_PCODE') == 'PH01055']
    for feature in selected:
        properties = feature['properties']
        name = properties.get('psgc_name') or properties.get({'barangays': 'ADM4_EN', 'municipalities': 'ADM3_EN', 'provinces': 'ADM2_EN'}[kind])
        if name in ['Osmena Sr.', 'Osme\u00f1a Sr']:
            name = 'Osme\u00f1a Sr.'
        feature['properties'] = {'name': name, 'psgc_code': properties['psgc_code'], 'source_name': properties.get('ADM4_EN') or properties.get('ADM3_EN') or properties.get('ADM2_EN'), 'reference_date': '2023-11-06'}
    if kind == 'barangays':
        assert len(selected) == 13 and set(feature['properties']['name'] for feature in selected) == set(names)
    result = {'type': 'FeatureCollection', 'features': selected}
    filename = {'barangays': 'basista-barangays.geojson', 'municipalities': 'pangasinan-cities-municipalities.geojson', 'provinces': 'pangasinan-province.geojson'}[kind]
    encoded = json.dumps(result, ensure_ascii=False, separators=(',', ':')).encode()
    (destination / filename).write_bytes(encoded)
    outputs.append({'file': filename, 'features': len(selected), 'bytes': len(encoded), 'sha256': hashlib.sha256(encoded).hexdigest()})
    if kind == 'barangays':
        coordinates = []
        def visit(value):
            if isinstance(value, list) and len(value) >= 2 and isinstance(value[0], (float, int)):
                coordinates.append(value)
            elif isinstance(value, list):
                for child in value:
                    visit(child)
        for feature in selected:
            visit(feature['geometry']['coordinates'])
        bounds = [min(point[0] for point in coordinates), min(point[1] for point in coordinates), max(point[0] for point in coordinates), max(point[1] for point in coordinates)]
        print(json.dumps({'barangays': names, 'bounds': bounds, 'center': [(bounds[0] + bounds[2]) / 2, (bounds[1] + bounds[3]) / 2]}))
manifest = {'source': 'https://github.com/bendlikeabamboo/barangay-boundaries-repository/releases/tag/v2026.4.13.0', 'archive_sha256': expected, 'geometry_snapshot': 'NAMRIA 2023-11-06 / PSA PSGC 2023-10-24', 'attribution': 'Philippine Statistics Authority (PSA), NAMRIA; curated by Manuel Balmeo', 'license_note': 'Upstream MIT license applies to code; data attribution belongs to PSA/NAMRIA. Source redistributes data for research/interoperability.', 'changes': 'Selected Basista barangays and Pangasinan context; reduced properties; display spelling Osmeña Sr. follows the user; geometry retained unchanged from the upstream simplified release.', 'limitations': 'Reference geometry, not municipal survey or legal jurisdiction acceptance. No personal/incident data.', 'outputs': outputs}
(destination / 'provenance.json').write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps(outputs))

# Always finish with the detailed local layers; do not silently ship the coarse
# street-level polygons that were rejected during visual review.
import runpy
runpy.run_path(str(root / 'scripts/extract-basista-detailed-boundaries.py'), run_name='__main__')
