// Import-time helper only. svgpath 2.6.0 is installed outside the plugin.
// Expand arcs, shorthand and relative coordinates for mPDF's SVG parser.
const fs = require('node:fs');
const svgpath = require(process.argv[2]);
const paths = JSON.parse(fs.readFileSync(0, 'utf8'));
process.stdout.write(JSON.stringify(paths.map(path => {
    const parsed = svgpath(path);
    if (parsed.err) throw new Error(parsed.err);
    return parsed.unshort().unarc().abs().segments.map(segment =>
        segment.map(value => typeof value === 'number' ? Number(value.toFixed(6)) : value).join(' ')
    ).join(' ');
})));
