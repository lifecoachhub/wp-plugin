<?php
/**
 * Global functions for LifeCoachHub plugin
 *
 * @package LifeCoachHub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Check if a class exists safely
 *
 * @param string $class_name The class name to check
 * @return bool True if class exists, false otherwise
 */
function lifecoachhub_class_exists( $class_name ) {
    try {
        return class_exists( $class_name );
    } catch ( Exception $e ) {
        error_log( 'LifeCoachHub: Error checking class existence - ' . $e->getMessage() );
        return false;
    }
}

/**
 * Safe class instantiation
 *
 * @param string $class_name The class name to instantiate
 * @param array  $args       Arguments to pass to constructor
 * @return object|null The class instance or null if failed
 */
function lifecoachhub_safe_instantiate( $class_name, $args = array() ) {
    if ( ! lifecoachhub_class_exists( $class_name ) ) {
        error_log( "LifeCoachHub: Class {$class_name} not found" );
        return null;
    }

    try {
        return new $class_name( ...$args );
    } catch ( Exception $e ) {
        error_log( "LifeCoachHub: Error instantiating {$class_name} - " . $e->getMessage() );
        return null;
    }
}

/**
 * Check if plugin dependencies are met
 *
 * @return bool True if all dependencies are met
 */
function lifecoachhub_dependencies_met() {
    $required_classes = array(
        'LifeCoachHub\\Admin\\LifeCoachHub_Admin',
    );

    foreach ( $required_classes as $class ) {
        if ( ! lifecoachhub_class_exists( $class ) ) {
            return false;
        }
    }

    return true;
}
