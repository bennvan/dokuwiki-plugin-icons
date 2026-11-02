#!/usr/bin/env python3
"""Convert the official Font Awesome Free npm archive into bundled Iconify sets.

Usage: python3 tools/import-fontawesome.py /path/to/fontawesome-free-7.3.1.tgz
No network access or third-party Python packages are required.
"""
import base64
import gzip
import hashlib
import json
from pathlib import Path
import sys
import tarfile

VERSION = '7.3.1'
INTEGRITY = 'wmglKKPDIkgV3aWlZzWECCPoGIkYCulzBwxG9+w7rc5BGapZ6cPMpoPOT8k36J0Ni7PPX6c/rsoMWfS4d1MUMg=='
ROOT = Path(__file__).resolve().parents[1]


def main(archive):
    payload = Path(archive).read_bytes()
    if base64.b64encode(hashlib.sha512(payload).digest()).decode() != INTEGRITY:
        raise ValueError('Archive does not match the official npm integrity hash')
    with tarfile.open(archive) as package:
        metadata = json.load(package.extractfile('package/metadata/icon-families.json'))
        license_text = package.extractfile('package/LICENSE.txt').read()
        assert json.load(package.extractfile('package/package.json'))['version'] == VERSION

    sets = {style: {} for style in ('solid', 'regular', 'brands')}
    for name, data in sorted(metadata.items()):
        for variant in data['familyStylesByLicense']['free']:
            style = variant['style']
            if variant['family'] != 'classic' or style not in sets:
                continue
            svg = data['svgs']['classic'][style]
            raw = svg['raw']
            sets[style][name] = {
                'body': raw[raw.index('>') + 1:raw.rindex('</svg>')],
                'width': svg['width'], 'height': svg['height'],
            }

    # The short/default pack prefers solid for names shared across styles.
    combined = {**sets['regular'], **sets['brands'], **sets['solid']}
    collections = json.loads((ROOT / 'assets/iconify/collections.json').read_text())
    packs = {'fa': ('Font Awesome Free', combined)}
    packs.update({f'fa-{style}': (f'Font Awesome Free {style.title()}', icons)
                  for style, icons in sets.items()})
    for prefix, (name, icons) in packs.items():
        samples = {'fa': ['wrench', 'house', 'github'],
                   'fa-solid': ['wrench', 'house', 'user'],
                   'fa-regular': ['bell', 'heart', 'user'],
                   'fa-brands': ['github', 'python', 'docker']}[prefix]
        info = {
            'name': name, 'version': VERSION, 'total': len(icons),
            'author': {'name': 'Fonticons, Inc.', 'url': 'https://fontawesome.com'},
            'license': {'title': 'CC BY 4.0', 'spdx': 'CC-BY-4.0',
                        'url': 'https://creativecommons.org/licenses/by/4.0/'},
            'samples': samples,
            'category': 'Brands / Social' if prefix == 'fa-brands' else 'General',
            'palette': False,
        }
        collection = {'prefix': prefix, 'info': info, 'width': 512, 'height': 512,
                      'icons': dict(sorted(icons.items()))}
        encoded = json.dumps(collection, separators=(',', ':')).encode()
        (ROOT / f'assets/iconify/json/{prefix}.json.gz').write_bytes(gzip.compress(encoded, mtime=0))
        collections[prefix] = {**info, 'author': info['author']['name'],
                               'url': info['author']['url'], 'license': 'CC BY 4.0',
                               'licenseUrl': info['license']['url']}
        print(f'{prefix}: {len(icons)} icons')
    (ROOT / 'assets/iconify/collections.json').write_text(json.dumps(collections, indent='\t') + '\n')
    (ROOT / 'assets/fontawesome-LICENSE.txt').write_bytes(license_text)


if __name__ == '__main__':
    if len(sys.argv) != 2:
        raise SystemExit(__doc__)
    main(sys.argv[1])
