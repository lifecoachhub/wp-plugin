/**
 * External dependencies
 */
const { join } = require('path');

/**
 * Gets the package.json file path.
 *
 * @return {string} Package.json file path.
 */
function getPackagePath() {
	return join(process.cwd(), 'package.json');
}

module.exports = {
	getPackagePath,
};
