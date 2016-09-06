/**
 * JavaScript the Semantic Maps extension.
 * @see https://www.mediawiki.org/wiki/Extension:Semantic_Maps
 *
 * @licence GNU GPL v2++
 * @author Peter Grassberger < petertheone@gmail.com >
 */
window.sm = new ( function( $, mw, smw ) {
    'use strict';

    this.buildQueryString = function( query, ajaxcoordproperty, top, right, bottom, left ) {
        var isCompoundQuery = query.indexOf('|') > -1;
        var query = query.split('|');
        $.each( query, function ( index ) {
            query[index] += ' [[' + ajaxcoordproperty + '::+]] ';
            query[index] += '[[' + ajaxcoordproperty + '::>' + bottom + '°, ' + left + '°]] ';
            query[index] += '[[' + ajaxcoordproperty + '::<' + top + '°, ' + right + '°]]';
            if (!isCompoundQuery) {
                query[index] += '|?' + ajaxcoordproperty;
            } else {
                query[index] += ';?' + ajaxcoordproperty;
            }
        } );
        return query.join(' | ');
    };

    this.ajaxUpdateMarker = function( map, query, icon ) {
        var isCompoundQuery = query.indexOf(';') > -1;
        var action = isCompoundQuery ? 'compoundquery' : 'ask';

        var api = new smw.Api();

        return api.fetch( query, true, action ).done( function( data ) {
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
                        text: '<b><a href="' + location.fullurl + '">' + location.fulltext + '</a></b>',
                        icon: icon
                    };
                    map.addMarker( markerOptions );
                }
            }
        } ).fail( function ( jqXHR, textStatus, errorThrown ) {
            throw new Error( 'Failed sending query: ' + textStatus + ', ' + errorThrown );
        } );
    };

} )( jQuery, mediaWiki, semanticMediaWiki );
