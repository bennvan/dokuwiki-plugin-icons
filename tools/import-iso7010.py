#!/usr/bin/env python3
"""Import the pinned iso-safety-signs checkout and Wikimedia attribution JSON.

Usage: python3 tools/import-iso7010.py CHECKOUT ATTRIBUTION_JSON SVGPATH_MODULE
Requires Node.js and svgpath 2.6.0 at import time only; no network access.
"""
import gzip
import hashlib
from html import escape
import json
from pathlib import Path
import re
import subprocess
import sys
import xml.etree.ElementTree as ET

COMMIT = '6fc253a3151c8d9dc7655125ebe0286056bb32d3'
ROOT = Path(__file__).resolve().parents[1]


def pdf_presentation(node, inherited_fill='black'):
    # mPDF gives presentation attributes precedence over style, unlike browsers.
    styles = dict(item.split(':', 1) for item in node.get('style', '').split(';') if ':' in item)
    for key, value in styles.items():
        key, value = key.strip(), value.strip()
        if key in ('fill', 'fill-rule', 'fill-opacity', 'stroke', 'stroke-width', 'stroke-opacity',
                   'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'opacity'):
            node.set(key, value)
    fill = node.get('fill', inherited_fill)
    if fill == 'inherit':
        fill = inherited_fill
    if fill == 'none' and node.tag.split('}')[-1] in ('path', 'rect', 'circle', 'ellipse', 'polygon', 'polyline', 'line'):
        # Even-odd has no meaning without a fill; mPDF otherwise emits invalid S*.
        node.set('fill-rule', 'nonzero')
        if 'fill-rule' in styles:
            node.set('style', re.sub(r'fill-rule\s*:[^;]+', 'fill-rule:nonzero', node.get('style')))
    for child in node:
        pdf_presentation(child, fill)


def main(checkout, attribution, svgpath_module):
    checkout = Path(checkout)
    revision = subprocess.check_output(['git', '-C', str(checkout), 'rev-parse', 'HEAD'], text=True).strip()
    if revision != COMMIT:
        raise ValueError('Review the upstream revision before changing the pin')
    source = checkout / 'packages/@iso-safety-signs'
    text = (source / 'core/src/signs.generated.ts').read_text()
    credits = json.loads(Path(attribution).read_text())
    svg_strings = [json.loads(value) for value in re.findall(r'^    svg: (".*"),$', text, re.M)]
    paths = list(dict.fromkeys(node.get('d') for svg in svg_strings
                              for node in ET.fromstring(svg).iter() if node.get('d')))
    converted = subprocess.check_output(
        ['node', str(ROOT / 'tools/normalize-svg-paths.cjs'), str(Path(svgpath_module).resolve())],
        input=json.dumps(paths), text=True)
    path_map = dict(zip(paths, json.loads(converted), strict=True))
    icons, aliases, sources = {}, {}, {}
    # Read generated string literals as JSON, never execute upstream code.
    for block in text.split('\n  {\n')[1:]:
        fields = {key: json.loads(value) for key, value in
                  re.findall(r'^    (code|name|id|svg): (".*"),$', block, re.M)}
        asset = json.loads(re.search(r'^      svg: (".*"),$', block, re.M)[1])
        svg = fields['svg']
        root = ET.fromstring(svg)
        ET.register_namespace('', 'http://www.w3.org/2000/svg')
        ET.register_namespace('xlink', 'http://www.w3.org/1999/xlink')
        for node in root.iter():
            if node.tag.split('}')[-1] in ('script', 'foreignObject', 'image'):
                raise ValueError('Unexpected SVG element in ' + asset)
            for key, value in node.attrib.items():
                if key.lower().startswith('on') or (key.endswith('href') and not value.startswith('#')):
                    raise ValueError('Unexpected active/external SVG attribute in ' + asset)
            if node.get('d'):
                node.set('d', path_map[node.get('d')])
            if node.get('class'):
                node.set('class', ' '.join(fields['id'] + '-' + name for name in node.get('class').split()))
            if node.tag.endswith('}style') and node.text:
                node.text = re.sub(r'\.([A-Za-z_][\w-]*)', lambda m: '.' + fields['id'] + '-' + m[1], node.text)
        pdf_presentation(root)
        view = root.get('viewBox')
        x, y, width, height = (map(float, view.split()) if view else
                               (0, 0, float(root.get('width').removesuffix('px')), float(root.get('height').removesuffix('px'))))
        scale = 512 / width
        svg = ET.tostring(root, encoding='unicode')
        body = svg[svg.index('>') + 1:svg.rindex('</svg>')]
        body = re.sub(r'>\s+<', '><', body).strip()
        # Normalise fractional viewBoxes without rounding away edges of a sign.
        attributes = ''.join(f' {key}="{escape(value, quote=True)}"' for key, value in root.attrib.items()
                             if key not in ('width', 'height', 'viewBox') and not key.startswith('{'))
        body = f'<g{attributes} transform="matrix({scale:.12g} 0 0 {scale:.12g} {-x*scale:.12g} {-y*scale:.12g})">{body}</g>'
        name = fields['id']
        icons[name] = {'body': body, 'width': 512, 'height': round(height * scale, 8)}
        aliases[fields['code'].lower()] = {'parent': name}
        if asset not in credits or not credits[asset]['metadata'].get('LicenseShortName'):
            raise ValueError('Missing artwork licence: ' + asset)
        sources[name] = {**credits[asset], 'asset': asset,
                         'sha256': hashlib.sha256((source / 'assets/assets' / asset).read_bytes()).hexdigest()}
    if len(icons) != 332:
        raise ValueError('Expected all 332 upstream signs')
    info = {
        'name': 'ISO 7010 Safety Signs', 'version': '2.0.0', 'total': len(icons),
        'author': {'name': 'Wikimedia contributors / Karl Norling', 'url': 'https://github.com/karlnorling/iso-safety-signs'},
        'license': {'title': 'Per-sign licences', 'url': 'https://github.com/karlnorling/iso-safety-signs#license'},
        'samples': ['m003', 'w001', 'f001'], 'category': 'Safety', 'palette': True,
    }
    data = {'prefix': 'iso7010', 'info': info, 'width': 512, 'height': 512,
            'icons': dict(sorted(icons.items())), 'aliases': dict(sorted(aliases.items()))}
    (ROOT / 'assets/iconify/json/iso7010.json.gz').write_bytes(
        gzip.compress(json.dumps(data, separators=(',', ':')).encode(), mtime=0))
    collections_path = ROOT / 'assets/iconify/custom-collections.json'
    collections = json.loads(collections_path.read_text())
    collections['iso7010'] = {**info, 'author': info['author']['name'], 'url': info['author']['url'],
                             'license': info['license']['title'], 'licenseUrl': info['license']['url']}
    collections_path.write_text(json.dumps(collections, indent='\t') + '\n')
    (ROOT / 'assets/iso7010-attribution.json').write_text(json.dumps({
        'upstream': info['author']['url'], 'commit': COMMIT,
        'modifications': 'Upstream optimised SVGs; paths expanded to absolute commands and arcs converted to Bezier curves for mPDF; coordinates and presentation attributes normalised; CSS classes scoped per sign; original colours retained.',
        'signs': sources}, indent=2) + '\n')
    (ROOT / 'assets/iso7010-LICENSE.txt').write_text(
        'Upstream code licence (not a blanket licence for the artwork):\n\n' +
        (checkout / 'LICENSE').read_text() + '\n\nArtwork attribution and individual licences: iso7010-attribution.json\n')
    print(f'Imported {len(icons)} signs and {len(aliases)} short code aliases')


if __name__ == '__main__':
    main(*sys.argv[1:])
