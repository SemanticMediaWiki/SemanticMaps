/**
 * JavaScript the Semantic Maps extension.
 * @see https://www.mediawiki.org/wiki/Extension:Semantic_Maps
 *
 * @licence GNU GPL v2++
 * @author Peter Grassberger < petertheone@gmail.com >
 */
window.sm = new ( function( $, smw ) {
    'use strict';

    this.buildQueryString = function( query, ajaxcoordproperty, top, right, bottom, left ) {
        query += ' [[' + ajaxcoordproperty + '::+]] ';
        query += '[[' + ajaxcoordproperty + '::>' + bottom + '°, ' + left + '°]] ';
        query += '[[' + ajaxcoordproperty + '::<' + top + '°, ' + right + '°]]';
        query += '|?' + ajaxcoordproperty;
        return query;
    };

    this.ajaxUpdateMarker = function( map, query ) {
        var api = new smw.Api();

        return api.fetch( query ).done( function( data ) {
            if ( !data.hasOwnProperty( 'query' ) ||
                    !data.query.hasOwnProperty( 'results' ) ) {
                return;
            }
            map.removeMarkers();
            for ( var property in data.query.results ) {
                if ( data.query.results.hasOwnProperty( property ) ) {
                    var location = data.query.results[property];
                    var coordinates = location.printouts[map.options.ajaxcoordproperty][0];
                    var markerOptions = {
                        lat: coordinates.lat,
                        lon: coordinates.lon,
                        title: location.fulltext,
                        text: '<b><a href="' + location.fullurl + '">' + location.fulltext + '</a></b>'
                    };
                    map.addMarker( markerOptions );
                }
            }
        } ).fail( function ( error ) {
            throw new Error( 'Failed sending query: ' + error );
        } );
    };

} )( jQuery, window.semanticMediaWiki );
