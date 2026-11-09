"""Replace the coarse release with detailed NAMRIA-derived WGS84 geometry.

Requires pyshp==2.3.1 in .cache/gis-tools, and the two hash-verified Git LFS
archives documented in the map handover. No dependency enters the phone app.
"""
import hashlib
import io
import json
import sys
import zipfile
from pathlib import Path

root = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(root / '.cache/gis-tools'))
import shapefile  # noqa: E402

destination = root / 'hazard-client/public/boundaries'
scope = json.loads((root / 'hazard-client/src/config/municipality.json').read_text(encoding='utf-8'))
manifest = json.loads((destination / 'provenance.json').read_text(encoding='utf-8'))
sources = []

def coordinates(value):
    if isinstance(value, (float, int)):
        return round(value, 6)
    return [coordinates(child) for child in value]

for kind, prefix, expected in [
    ('barangays', 'BgySubMuns.shp', '904ff4ad2a8cfcfc06d23a6f4109bb1b142689d4cb4a8863d8bf650e99476186'),
    ('municipalities', 'Municities.shp', '9bb847cfed70656b1ad67ac8648da7867a52df69e3150eff141cd8345b9027c2'),
]:
    archive = root / f'.cache/namria-detailed-{kind}.zip'
    with archive.open('rb') as stream:
        digest = hashlib.file_digest(stream, 'sha256').hexdigest()
    assert digest == expected, 'Unexpected detailed source archive'
    zipped = zipfile.ZipFile(archive)
    assert zipped.read(prefix + '.prj').decode().startswith('GEOGCS["GCS_WGS_1984"'), 'Projected geometry needs conversion; never treat metres as degrees'
    reader = shapefile.Reader(shp=zipped.open(prefix + '.shp'), shx=io.BytesIO(zipped.read(prefix + '.shx')), dbf=io.BytesIO(zipped.read(prefix + '.dbf')), encoding='latin1')
    records = []
    for record in reader.iterRecords():
        properties = record.as_dict()
        if (kind == 'barangays' and properties.get('adm3_pcode') == 'PH0105509') or (kind == 'municipalities' and properties.get('adm2_pcode') == 'PH01055'):
            records.append((record.oid, properties))
    features = []
    vertex_count = 0
    for index, properties in sorted(records):
        shape = reader.shape(index)
        if kind == 'municipalities':
            west, south, east, north = scope['bounds']
            left, bottom, right, top = shape.bbox
            if right < west or left > east or top < south or bottom > north:
                continue
        vertex_count += len(shape.points)
        name = properties.get('name') or properties.get('adm4_en') or properties.get('adm3_en')
        if kind == 'barangays' and ('Osme' in name or str(properties['adm4_pcode']).endswith('010')):
            name = 'Osme\u00f1a Sr.'
        geometry = shape.__geo_interface__
        geometry['coordinates'] = coordinates(geometry['coordinates'])
        features.append({'type': 'Feature', 'properties': {'name': name, 'psgc_code': str(int(properties['psgc_code'])).zfill(10), 'source': 'PSA/NAMRIA via altcoder', 'reference': '2023 source'}, 'geometry': geometry})
    if kind == 'barangays':
        assert len(features) == 13
        assert set(feature['properties']['name'] for feature in features) == set(scope['barangays'])
    else:
        assert len([feature for feature in features if feature['properties']['name'] == 'Basista']) == 1
    filename = 'basista-barangays.geojson' if kind == 'barangays' else 'pangasinan-cities-municipalities.geojson'
    output = json.dumps({'type': 'FeatureCollection', 'features': features}, ensure_ascii=False, separators=(',', ':')).encode()
    (destination / filename).write_bytes(output)
    manifest['outputs'] = [item for item in manifest['outputs'] if item['file'] != filename]
    manifest['outputs'].append({'file': filename, 'features': len(features), 'vertices': vertex_count, 'bytes': len(output), 'sha256': hashlib.sha256(output).hexdigest()})
    sources.append({'level': kind, 'repository': 'https://github.com/altcoder/philippines-psgc-shapefiles', 'archive_sha256': digest, 'archive_bytes': archive.stat().st_size, 'coordinate_system': 'Original WGS84 geographic layer', 'original_source': 'PSA/NAMRIA 2023 via OCHA COD as documented by the upstream maintainer'})
manifest['detailed_sources'] = sources
manifest['changes'] = 'Basista 13 barangays and intersecting nearby municipality context use detailed WGS84 geometry, rounded to six decimals only (about 0.1m coordinate precision, not positional accuracy). Province context retains the simplified release. Reduced properties; Osmeña Sr. follows the user. No invented borders. Distant municipalities are omitted to keep phone downloads small.'
manifest['attribution'] = 'Philippine Statistics Authority (PSA), NAMRIA; detailed extracts via altcoder; province context via Manuel Balmeo'
manifest['limitations'] = 'Reference geometry from published source snapshots, not municipal survey or legal jurisdiction acceptance. No personal/incident data. Upstream simplification discarded after visual review.'
(destination / 'provenance.json').write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps(manifest['outputs']))
