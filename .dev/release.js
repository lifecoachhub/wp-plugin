/**
 * External dependencies
 */
const fs = require('fs');
const path = require('path');
const AdmZip = require('adm-zip');
const { sync: glob } = require('fast-glob');
const { stdout } = require('process');

/**
 * Internal dependencies
 */
const { hasPackageProp, getPackageProp } = require('./package');

const name = getPackageProp('name');
stdout.write(`Creating archive for \`${name}\` plugin... 🎁\n\n`);

let files = [];
if (hasPackageProp('files')) {
	stdout.write('Using the `files` field from `package.json` to detect files:\n\n');
	const packageFiles = getPackageProp('files');
	
	// Convert package files to actual file paths
	packageFiles.forEach(pattern => {
		if (pattern.endsWith('/**')) {
			// Handle directory patterns
			const dir = pattern.replace('/**', '');
			if (fs.existsSync(dir)) {
				const dirFiles = glob(`${dir}/**/*`, { onlyFiles: true });
				files = files.concat(dirFiles);
			}
		} else if (pattern.includes('*')) {
			// Handle glob patterns
			const globFiles = glob(pattern, { onlyFiles: true });
			files = files.concat(globFiles);
		} else {
			// Handle direct file references
			if (fs.existsSync(pattern)) {
				files.push(pattern);
			}
		}
	});
} else {
	stdout.write('Using Plugin Handbook best practices to discover files:\n\n');
	files = glob(
		[
			'admin/**',
			'build/**',
			'includes/**',
			'languages/**',
			'styles/**',
			'public/**',
			'assets/**',
			'vendor/**',
			`${name}.php`,
			'uninstall.php',
			'block.json',
			'changelog.*',
			'license.*',
            'composer.json',
			'readme.*',
		],
		{
			caseSensitiveMatch: false,
			onlyFiles: true,
		}
	);
}

// Remove duplicates and sort
files = [...new Set(files)].sort();

if (files.length === 0) {
	stdout.write('❌ No files found to package!\n');
	process.exit(1);
}

// Create the zip
const zip = new AdmZip();

files.forEach((file) => {
	stdout.write(`  Adding \`${file}\`.\n`);
	
	// Add file to zip with proper directory structure
	// All files should be in the plugin directory inside the zip
	const zipPath = path.join(name, file);
	zip.addLocalFile(file, path.dirname(zipPath));
});

// Clean up existing files
const zipFileName = `${name}.zip`;
const pluginDir = `./${name}`;

// Remove existing zip and directory
if (fs.existsSync(zipFileName)) {
	fs.unlinkSync(zipFileName);
	stdout.write(`Removed existing \`${zipFileName}\`.\n`);
}

if (fs.existsSync(pluginDir)) {
	fs.rmSync(pluginDir, { recursive: true, force: true });
	stdout.write(`Removed existing \`${pluginDir}\` directory.\n`);
}

// Write the zip file
zip.writeZip(zipFileName);
stdout.write(`\n✅ Done. \`${zipFileName}\` release is ready! 🎉\n`);
stdout.write(`📦 Total files packaged: ${files.length}\n`);
