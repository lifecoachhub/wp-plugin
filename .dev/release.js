/**
 * External dependencies
 */
const fs = require('fs');
const AdmZip = require('adm-zip');
const { sync: glob } = require('fast-glob');
const packlist = require('npm-packlist');
const { dirname } = require('path');
const { stdout } = require('process');

/**
 * Internal dependencies
 */
const { hasPackageProp, getPackageProp } = require('./package');

const name = getPackageProp('name');
stdout.write(`Creating archive for \`${name}\` plugin... 🎁\n\n`);
const zip = new AdmZip();

async function createPluginZip() {
	let files = [];
	
	if (hasPackageProp('files')) {
		stdout.write(
			'Using the `files` field from `package.json` to detect files:\n\n'
		);
		// npm-packlist returns a promise in newer versions
		try {
			files = await packlist();
		} catch (error) {
			// Fallback to sync version if available
			files = packlist.sync ? packlist.sync() : [];
		}
	} else {
		stdout.write('Using Plugin Handbook best practices to discover files:\n\n');
		// See https://developer.wordpress.org/plugins/plugin-basics/best-practices/#file-organization.
		files = glob(
			[
				'admin/**',
				'build/**',
				'includes/**',
				'languages/**',
				'styles/**',
				'public/**',
				`${name}.php`,
				'uninstall.php',
				'block.json',
				'changelog.*',
				'license.*',
				'readme.*',
			],
			{
				caseSensitiveMatch: false,
			}
		);
	}

	files.forEach((file) => {
		stdout.write(`  Adding \`${file}\`.\n`);
		zip.addLocalFile(file, dirname(file));
	});

	fs.rm(`./${name}`, { recursive: true }, (err) => {
		if (err) {
			stdout.write(`\nDirectory does not exist, so it did not delete.\n`);
		} else {
			stdout.write(`\nExisting \`${name}\` folder deleted.\n`);
		}

		zip.extractAllTo(`./${name}`, true);
		zip.writeZip(`./${name}.zip`);
		stdout.write(`\nDone. \`${name}\` release is ready! 🎉\n`);
	});
}

// Run the function
createPluginZip().catch((error) => {
	console.error('Error creating plugin zip:', error);
	process.exit(1);
});
