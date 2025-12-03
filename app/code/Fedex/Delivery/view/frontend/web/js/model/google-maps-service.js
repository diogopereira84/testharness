/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    let map;
    let markers = [];
    const DEFAULT_MARKER_COLOR = '#54646b';
    const SELECTED_MARKER_COLOR = '#4d148c';
    const MARKER_GLYPH_COLOR = '#FFFFFF';
    const MARKER_SCALE = 1.2;

    return {
        /**
         * Initialize Google Maps
         * @param {Array} locations - Array of pickup locations
         * @param {string} mapContainerId - ID of the map container element
         */
        initMap(locations, mapContainerId) {
            try {
                if (!locations?.length) {
                    return;
                }

                const mapProp = {
                    center: { 
                        lat: parseFloat(locations[0].location.geoCode.latitude), 
                        lng: parseFloat(locations[0].location.geoCode.longitude) 
                    },
                    zoom: 12,
                    mapId: 'DEMO_MAP_ID'
                };

                map = new google.maps.Map(document.getElementById(mapContainerId), mapProp);

                this.clearMarkers();
                
                const infowindow = new google.maps.InfoWindow();
                
                locations.forEach((element, index) => {
                    const defaultIcon = new google.maps.marker.PinElement({
                        glyph: `${index + 1}`,
                        background: DEFAULT_MARKER_COLOR,
                        borderColor: DEFAULT_MARKER_COLOR,
                        glyphColor: MARKER_GLYPH_COLOR,
                        scale: MARKER_SCALE
                    });

                    const selectedIcon = new google.maps.marker.PinElement({
                        glyph: `${index + 1}`,
                        background: SELECTED_MARKER_COLOR,
                        borderColor: SELECTED_MARKER_COLOR,
                        glyphColor: MARKER_GLYPH_COLOR,
                        scale: MARKER_SCALE
                    });

                    const marker = new google.maps.marker.AdvancedMarkerElement({
                        map,
                        position: { 
                            lat: parseFloat(element.location.geoCode.latitude), 
                            lng: parseFloat(element.location.geoCode.longitude) 
                        },
                        content: index === 0 ? selectedIcon.element : defaultIcon.element
                    });
                    
                    markers.push(marker);
                    
                    google.maps.event.addListener(marker, 'click', () => {
                        infowindow.setContent(element.location.name);
                        infowindow.open(map, marker);
                    });
                });

            } catch(err) {
                console.error('Google Maps Error:', err);
                const mapContainer = document.getElementById(mapContainerId);
                if (mapContainer) {
                    mapContainer.style.display = 'none';
                }
            }
        },

        waitForGoogleMapsToLoad: function() {
            return new Promise((resolve) => {
                // Resolve immediately if Google Maps is already available
                if (window.google && window.google.maps) {
                    return resolve();
                }

                $(document).on('google-maps-loaded', function() {
                    resolve();
                });
            });
        },

        /**
         * Clear all markers from the map
         */
        clearMarkers() {
            markers.forEach(marker => marker.setMap(null));
            markers = [];
        },

        /**
         * Highlight a pickup location on the map and center view
         * @param {Object} location - The pickup location to highlight
         * @param {Array} allLocations - All pickup locations for context
         */
        highlightPickupLocation(selectedPickupLocation, allPickupLocations) {
            if (!selectedPickupLocation?.location?.geoCode) return;

            try {
                // Clear previous selections
                this.clearSelection();

                // Find the index of the selected location
                const selectedIndex = allPickupLocations.findIndex(pickup => pickup.location.id === selectedPickupLocation.location.id);
                if (selectedIndex === -1) return;

                // Update marker styles
                markers.forEach((marker, index) => {
                    const isSelected = index === selectedIndex;
                    const locationData = allPickupLocations[index];
                    
                    if (locationData && locationData.location.geoCode) {
                        const markerElement = this.createMarkerElement(index, isSelected);
                        marker.content = markerElement.element;
                    }
                });

                // Center map on selected location
                if (map) {
                    map.setCenter({
                        lat: parseFloat(selectedPickupLocation.location.geoCode.latitude),
                        lng: parseFloat(selectedPickupLocation.location.geoCode.longitude)
                    });
                    map.setZoom(14);
                }

            } catch (err) {
                console.error('Error selecting pickup location on map:', err);
            }
        },

        /**
         * Clear selection highlighting from all markers
         */
        clearSelection() {
            // Reset all markers to default state
            markers.forEach((marker, index) => {
                const markerElement = this.createMarkerElement(index, false);
                marker.content = markerElement.element;
            });
        },

        /**
         * Create a marker element with appropriate styling
         * @param {number} index - The marker index
         * @param {boolean} isSelected - Whether this marker should be highlighted
         * @returns {google.maps.marker.PinElement} The configured marker element
         */
        createMarkerElement(index, isSelected) {
            return new google.maps.marker.PinElement({
                glyph: `${index + 1}`,
                background: isSelected ? SELECTED_MARKER_COLOR : DEFAULT_MARKER_COLOR,
                borderColor: isSelected ? SELECTED_MARKER_COLOR : DEFAULT_MARKER_COLOR,
                glyphColor: MARKER_GLYPH_COLOR,
                scale: MARKER_SCALE
            });
        },

        /**
         * Toggle map visibility
         * @param {boolean} show - Whether to show or hide the map
         */
        toggleMap(show) {
            const mapContainer = document.querySelector('.map-canvas');
            if (mapContainer) {
                mapContainer.style.display = show ? 'block' : 'none';
            }
        }
    };
});
