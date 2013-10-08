<?php

/**
 * Class for handling geographical SMW queries.
 *
 * @since 0.7.3
 *
 * @ingroup SemanticMaps
 * @file SM_QueryHandler.php
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMQueryHandler {

	protected $queryResult;
	protected $outputmode;

	/**
	 * @since 2.0
	 *
	 * @var array
	 */
	protected $geoShapes = array(
		'lines' => array(),
		'locations' => array(),
		'polygons' => array()
	);

	/**
	 * The template to use for the text, or false if there is none.
	 *
	 * @since 0.7.3
	 *
	 * @var string|boolean false
	 */
	protected $template = false;

	/**
	 * The global icon.
	 *
	 * @since 0.7.3
	 *
	 * @var string
	 */
	public $icon = '';

	/**
	 * The global text.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	public $text = '';

	/**
	 * The global title.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Make a separate link to the title or not?
	 *
	 * @since 0.7.3
	 *
	 * @var boolean
	 */
	public $titleLinkSeparate;

	/**
	 * Should link targets be made absolute (instead of relative)?
	 *
	 * @since 1.0
	 *
	 * @var boolean
	 */
	protected $linkAbsolute;

	/**
	 * The text used for the link to the page (if it's created). $1 will be replaced by the page name.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $pageLinkText;

	/**
	 * A separator to use between the subject and properties in the text field.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $subjectSeparator = '<hr />';

	/**
	 * Make the subject in the text bold or not?
	 *
	 * @since 1.0
	 *
	 * @var boolean
	 */
	protected $boldSubject = true;

	/**
	 * Show the subject in the text or not?
	 *
	 * @since 1.0
	 *
	 * @var boolean
	 */
	protected $showSubject = true;

	/**
	 * Hide the namepsace or not.
	 *
	 * @var boolean
	 */
	protected $hideNamespace = false;

	/**
	 * Constructor.
	 *
	 * @since 0.7.3
	 *
	 * @param SMWQueryResult $queryResult
	 * @param integer $outputmode
	 * @param boolean $linkAbsolute
	 * @param string $pageLinkText
	 * @param boolean $titleLinkSeparate
	 */
	public function __construct( SMWQueryResult $queryResult, $outputmode, $linkAbsolute = false, $pageLinkText = '$1', $titleLinkSeparate = false, $hideNamespace = false ) {
		$this->queryResult = $queryResult;
		$this->outputmode = $outputmode;

		$this->linkAbsolute = $linkAbsolute;
		$this->pageLinkText = $pageLinkText;
		$this->titleLinkSeparate = $titleLinkSeparate;
		$this->hideNamespace = $hideNamespace;
	}

	/**
	 * Sets the template.
	 *
	 * @since 1.0
	 *
	 * @param string $template
	 */
	public function setTemplate( $template ) {
		$this->template = $template === '' ? false : $template;
	}

	/**
	 * Sets the global icon.
	 *
	 * @since 1.0
	 *
	 * @param string $icon
	 */
	public function setIcon( $icon ) {
		$this->icon = $icon;
	}

	/**
	 * Sets the global title.
	 *
	 * @since 1.0
	 *
	 * @param string $title
	 */
	public function setTitle( $title ) {
		$this->title = $title;
	}

	/**
	 * Sets the global text.
	 *
	 * @since 1.0
	 *
	 * @param string $text
	 */
	public function setText( $text ) {
		$this->text = $text;
	}

	/**
	 * Sets the subject separator.
	 *
	 * @since 1.0
	 *
	 * @param string $subjectSeparator
	 */
	public function setSubjectSeparator( $subjectSeparator ) {
		$this->subjectSeparator = $subjectSeparator;
	}

	/**
	 * Sets if the subject should be made bold in the text.
	 *
	 * @since 1.0
	 *
	 * @param string $boldSubject
	 */
	public function setBoldSubject( $boldSubject ) {
		$this->boldSubject = $boldSubject;
	}

	/**
	 * Sets if the subject should shown in the text.
	 *
	 * @since 1.0
	 *
	 * @param string $showSubject
	 */
	public function setShowSubject( $showSubject ) {
		$this->showSubject = $showSubject;
	}

	/**
	 * Sets the text for the link to the page when separate from the title.
	 *
	 * @since 1.0
	 *
	 * @param string $text
	 */
	public function setPageLinkText( $text ) {
		$this->pageLinkText = $text;
	}

	public function getShapes() {
		$this->findShapes();
		return $this->geoShapes;
	}

        
	protected function findShapes() {
wfProfileIn( __METHOD__ ); 
#hlLog("findShapes start");
$results=$this->queryResult->getResults();

#hlDump("mResults=".print_r($results,TRUE)."\n\n");
#                $gw_locations_speedup=1;   # will be moved to some config file 
                if($gw_locations_speedup) {
                        $this->geoShapes=FindShapesHelper::findShapesLocationsFast( $results );
                        goto do_return;
                }
		while ( ( $row = $this->queryResult->getNext() ) !== false ) {
			$this->handleResultRow( $row );
		}
do_return:
#hlDump("geoShapes=".print_r($this->geoShapes,TRUE)."\n\n");                
#hlLog("findShapes end");
wfProfileOut( __METHOD__ ); 
	}

	/**
	 * Returns the locations found in the provided result row.
	 *
	 * @since 0.7.3
	 *
	 * @param array $row Array of SMWResultArray
	 *
	 * @return array of MapsLocation
	 */
	protected function handleResultRow( array /* of SMWResultArray */ $row ) {
		$locations = array();
		$properties = array();

		$title = '';
		$text = '';

global $hlCount;
$hlCount++;
		// Loop throught all fields of the record.
		foreach ( $row as $i => $resultArray ) {

if($hlCount>=0) {
#  $ser=serialize($resultArray);
#  hlLog("handleResultRow hlCount=".$hlCount." row=".$ser);
#  hlLog("handleResultRow hlCount=".$hlCount." row=".print_r($resultArray,TRUE));
}
			/* SMWPrintRequest */ $printRequest = $resultArray->getPrintRequest();

			// Loop throught all the parts of the field value.
#			while ( ( /* SMWDataValue */ $dataValue = $resultArray->getNextDataValue() ) !== false ) {
                do_while_dv:
wfProfileIn("HandleResultRow-getNextDataValue"); 
                        $dataValue = $resultArray->getNextDataValue();  # SMW_ResultArray.php +138
wfProfileOut("HandleResultRow-getNextDataValue"); 
                        if($dataValue !== false) {
wfProfileIn("HandleResultRow-dv-getTypeID"); 
                                $dt=$dataValue->getTypeID();
wfProfileOut("HandleResultRow-dv-getTypeID"); 
				if ( $dt == '_wpg' && $i == 0 ) {
wfProfileIn("HandleResultRow-handleResultSubject"); 
					list( $title, $text ) = $this->handleResultSubject( $dataValue );
wfProfileOut("HandleResultRow-handleResultSubject"); 
				}
				else if ( $dt == '_str' && $i == 0 ) {
wfProfileIn("HandleResultRow-dt_str"); 
					$title = $dataValue->getLongText( $this->outputmode, null );
					$text = $dataValue->getLongText( $this->outputmode, smwfGetLinker() );
wfProfileOut("HandleResultRow-dt_str"); 
				}
				else if ( $dt == '_gpo' ) {
wfProfileIn("HandleResultRow-dt_gpo"); 
					$dataItem = $dataValue->getDataItem();
					$polyHandler = new PolygonHandler ( $dataItem->getString() );
					$this->geoShapes[ $polyHandler->getGeoType() ][] = $polyHandler->shapeFromText();
wfProfileOut("HandleResultRow-dt_gpo"); 
				}
				else if ( $dt != '_geo' && $i != 0 ) {
wfProfileIn("HandleResultRow-handleResultProperty"); 
					$properties[] = $this->handleResultProperty( $dataValue, $printRequest );
wfProfileOut("HandleResultRow-handleResultProperty"); 
				}
				else {
wfProfileIn("HandleResultRow-locations"); 
                                        if ( $printRequest->getMode() == SMWPrintRequest::PRINT_PROP && $printRequest->getTypeID() == '_geo' ) {
					        $dataItem = $dataValue->getDataItem();

					        $location = MapsLocation::newFromLatLon( $dataItem->getLatitude(), $dataItem->getLongitude() );

					        if ( $location->isValid() ) {
						        $locations[] = $location;
					        }
                                        }
wfProfileOut("HandleResultRow-locations"); 
				}
                                goto do_while_dv;
			}
		}

		if ( count( $properties ) > 0 && $text !== '' ) {
			$text .= $this->subjectSeparator;
		}

		$icon = $this->getLocationIcon( $row );
# $icon = "/marker2.png";
# hlLog("getLocationIcon icon=".$icon);   no timing problem

                $bll= $this->buildLocationsList( $locations, $text, $icon, $properties, Title::newFromText( $title ));

#if($hlCount==10) {
#  $ser=serialize($bll);
#  hlLog("handleResultRow bll=".$ser);
#  hlLog("handleResultRow bll=".print_r($bll,TRUE));
#}
		$this->geoShapes['locations'] = array_merge( $this->geoShapes['locations'],$bll);
	}

	/**
	 * Handles a SMWWikiPageValue subject value.
	 * Gets the plain text title and creates the HTML text with headers and the like.
	 *
	 * @since 1.0
	 *
	 * @param SMWWikiPageValue $object
	 *
	 * @return array with title and text
	 */
	protected function handleResultSubject( SMWWikiPageValue $object ) {
		$title = $object->getLongText( $this->outputmode, null );
		$text = '';

		if ( $this->showSubject ) {
			if ( !$this->titleLinkSeparate && $this->linkAbsolute ) {
				$text = Html::element(
					'a',
					array( 'href' => $object->getTitle()->getFullUrl() ),
					$object->getTitle()->getText()
				);
			}
			else {
				$text = $object->getLongHTMLText( smwfGetLinker() );
			}

			if ( $this->boldSubject ) {
				$text = '<b>' . $text . '</b>';
			}

			if ( $this->titleLinkSeparate ) {
				$txt = $object->getTitle()->getText();

				if ( $this->pageLinkText !== '' ) {
					$txt = str_replace( '$1', $txt, $this->pageLinkText );
				}
				$text .= Html::element(
					'a',
					array( 'href' => $object->getTitle()->getFullUrl() ),
					$txt
				);
			}
		}

		return array( $title, $text );
	}

	/**
	 * Handles a single property (SMWPrintRequest) to be displayed for a record (SMWDataValue).
	 *
	 * @since 1.0
	 *
	 * @param SMWDataValue $object
	 * @param SMWPrintRequest $printRequest
	 *
	 * @return string
	 */
	protected function handleResultProperty( SMWDataValue $object, SMWPrintRequest $printRequest ) {
		if ( $this->template ) {
			if ( $object instanceof SMWWikiPageValue ) {
				return $object->getTitle()->getPrefixedText();
			} else {
				return $object->getLongText( SMW_OUTPUT_WIKI, NULL );
			}
		}

		if ( $this->linkAbsolute ) {
			$t = Title::newFromText( $printRequest->getHTMLText( NULL ), SMW_NS_PROPERTY );

			if ( $t instanceof Title && $t->exists() ) {
				$propertyName = $propertyName = Html::element(
					'a',
					array( 'href' => $t->getFullUrl() ),
					$printRequest->getHTMLText( NULL )
				);
			}
			else {
				$propertyName = $printRequest->getHTMLText( NULL );
			}
		}
		else {
			$propertyName = $printRequest->getHTMLText( smwfGetLinker() );
		}

		if ( $this->linkAbsolute ) {
			$hasPage = $object->getTypeID() == '_wpg';

			if ( $hasPage ) {
				$t = Title::newFromText( $object->getLongText( $this->outputmode, NULL ), NS_MAIN );
				$hasPage = $t->exists();
			}

			if ( $hasPage ) {
				$propertyValue = Html::element(
					'a',
					array( 'href' => $t->getFullUrl() ),
					$object->getLongText( $this->outputmode, NULL )
				);
			}
			else {
				$propertyValue = $object->getLongText( $this->outputmode, NULL );
			}
		}
		else {
			$propertyValue = $object->getLongText( $this->outputmode, smwfGetLinker() );
		}

		return $propertyName . ( $propertyName === '' ? '' : ': ' ) . $propertyValue;
	}

	/**
	 * Builds a set of locations with the provided title, text and icon.
	 *
	 * @since 1.0
	 *
	 * @param MapsLocation[] $locations
	 * @param string $text
	 * @param string $icon
	 * @param array $properties
	 * @param Title|null $title
	 *
	 * @return MapsLocation[]
	 */
	protected function buildLocationsList( array $locations, $text, $icon, array $properties, Title $title = null ) {
wfProfileIn( __METHOD__ ); 
		if ( $this->template ) {
			global $wgParser;
			$parser = $wgParser;
		}
		else {
			$text .= implode( '<br />', $properties );
		}

		if ( $title === null ) {
			$titleOutput = '';
		}
		else {
			$titleOutput = $this->hideNamespace ? $title->getText() : $title->getFullText();
		}

		foreach ( $locations as &$location ) {
			if ( $this->template ) {
				$segments = array_merge(
					array(
						$this->template,
						'title=' . $titleOutput,
						'latitude=' . $location->getLatitude(),
						'longitude=' . $location->getLongitude()
					),
					$properties
				);

				$text .= $parser->parse( '{{' . implode( '|', $segments ) . '}}', $parser->getTitle(), new ParserOptions() )->getText();
			}

			$location->setTitle( $titleOutput );
			$location->setText( $text );
			$location->setIcon( $icon );
		}
wfProfileOut( __METHOD__ ); 

		return $locations;
	}

	/**
	 * Get the icon for a row.
	 *
	 * @since 0.7.3
	 *
	 * @param array $row
	 *
	 * @return string
	 */
	protected function getLocationIcon( array $row ) {
		$icon = '';
		$legend_labels = array();

		// Look for display_options field, which can be set by Semantic Compound Queries
		// the location of this field changed in SMW 1.5
		$display_location = method_exists( $row[0], 'getResultSubject' ) ? $row[0]->getResultSubject() : $row[0];

		if ( property_exists( $display_location, 'display_options' ) && is_array( $display_location->display_options ) ) {
			$display_options = $display_location->display_options;
			if ( array_key_exists( 'icon', $display_options ) ) {
				$icon = $display_options['icon'];

				// This is somewhat of a hack - if a legend label has been set, we're getting it for every point, instead of just once per icon
				if ( array_key_exists( 'legend label', $display_options ) ) {

					$legend_label = $display_options['legend label'];

					if ( ! array_key_exists( $icon, $legend_labels ) ) {
						$legend_labels[$icon] = $legend_label;
					}
				}
			}
		} // Icon can be set even for regular, non-compound queries If it is, though, we have to translate the name into a URL here
		elseif ( $this->icon !== '' ) {
			$icon = MapsMapper::getFileUrl( $this->icon );
		}

		return $icon;
	}

	/**
	 * @param boolean $hideNamespace
	 */
	public function setHideNamespace( $hideNamespace ) {
		$this->hideNamespace = $hideNamespace;
	}

	/**
	 * @return boolean
	 */
	public function getHideNamespace() {
		return $this->hideNamespace;
	}

}

class FindShapesHelper extends SMWDIWikiPage {

        public function findShapesLocationsFast( $results ) {   # currently build duplicate structure
	        $geoShapes = array(
		        'lines' => array(),
		        'locations' => array(),
		        'polygons' => array()
	        );

                $dbr = wfGetDB( DB_SLAVE );

                $TitleRetSid=array();
                $TitleRetData=array();
                $res = $dbr->select( 'smw_object_ids', array( 'smw_id', 'smw_namespace', 'smw_title' ), array( "smw_namespace = 0" ) );
                while(($row = $res->fetchRow()) !== false) {
                        $sid=$row['smw_id'];
                        $title=$row['smw_title'];
                        $TitleRetSid[$title]=$sid;
                        $TitleRetData[$title]=array ( 'sid' => $sid );
                }
                # hlDump("select(TitleRetSid)=".print_r($TitleRetSid,TRUE)."\n\n");                

                $SidRetLat=array(); 
                $SidRetLon=array(); 
                $res = $dbr->select( 'smw_di_coords', array( 's_id', 'o_lat', 'o_lon' ) );
                while(($row = $res->fetchRow()) !== false) {
                        $sid=$row['s_id'];
                        $lat=$row['o_lat'];
                        $lon=$row['o_lon'];
                        $SidRetLat[$sid]=$lat;
                        $SidRetLon[$sid]=$lon;
                }  
                # hlDump("select(smw_di_coords/SidRetLat)=".print_r($SidRetLat,TRUE)."\n\n");                
                # hlDump("select(smw_di_coords/SidRetLon)=".print_r($SidRetLon,TRUE)."\n\n");                

		foreach ( $results as $i => $dwp) {
                        $title=$dwp->m_dbkey;
                        $icon= $dwp->display_options['icon'];
                        $sid= isset($TitleRetSid[$title])? $TitleRetSid[$title] : NULL;
                        if($sid !== NULL) {
                                $lat=isset($SidRetLat[$sid]) ? $SidRetLat[$sid] : NULL;
                                $lon=isset($SidRetLon[$sid]) ? $SidRetLon[$sid] : NULL;
                                if($lat !== NULL) {
                                        $TitleRetData[$title]['lat']=$lat;
                                        $TitleRetData[$title]['lon']=$lon;
                                        $ml=MapsLocation::newFromLatLon($lat,$lon);
                                        $title_stripped=preg_replace('/_/', ' ', $title);
                                        $ml->setTitle($title_stripped);  
                                        $ml->setIcon($icon);
                                        $text="<b><a href=\"/index.php/$title\" title=\"$title_stripped\">$title_stripped</a></b>";
                                        $ml->setText($text);  
                                        $geoShapes['locations'][] = $ml;
                                } else {
                                        # hlDump("no lat for title=".$title);
                                }
                        } else {
                                # hlDump("no sid for title=".$title);
                        }
                }
                # hlDump("TitleRetData=".print_r($TitleRetData,TRUE)."\n\n");       

                # hlDump("geoShapes(new)=".print_r($geoShapes,TRUE)."\n\n");                

                return $geoShapes;
        }

}


