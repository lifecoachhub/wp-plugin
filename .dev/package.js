/**
 * External dependencies
 */
const { readFileSync } = require('fs');
const { join } = require('path');

/**
 * Internal dependencies
 */
const { getPackagePath } = require('./utils');

/**
 * Gets the package.json file contents.
 *
 * @return {Object} Package data.
 */
function getPackage() {
	return JSON.parse(readFileSync(getPackagePath(), 'utf8'));
}

/**
 * Gets a package property.
 *
 * @param {string} prop Property name.
 * @return {*} Property value.
 */
function getPackageProp(prop) {
	return getPackage()[prop];
}

/**
 * Returns true if the package has the given property, false otherwise.
 *
 * @param {string} prop Property name.
 * @return {boolean} True if the package has the property, false otherwise.
 */
function hasPackageProp(prop) {
	return getPackage().hasOwnProperty(prop);
}

module.exports = {
	getPackage,
	getPackageProp,
	hasPackageProp,
};
