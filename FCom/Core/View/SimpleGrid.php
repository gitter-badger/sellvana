<?php

class FCom_Core_View_SimpleGrid extends FCom_Core_View_HtmlGrid
{
    static protected $_defaultActions = array(
        'refresh' => true,
        'link_to_page' => false,
        'sort' => true,
    );

    public function rowsHtml( $rows = null )
    {
        $grid = $this->get( 'grid' );
        $rows = $grid[ 'config' ][ 'data' ];
        $gridId = $grid[ 'config' ][ 'id' ];
        $columns = $grid[ 'config' ][ 'columns' ];

        $trArr = array();
        foreach ( $rows as $rowId => $row ) {
            $row->_id = $rowId;
            $trAttr = array();
            $trAttr[ 'id' ] = "data-row--{$gridId}--{$rowId}";
            $trAttr[ 'data-id' ] = $row->get( $grid[ 'config' ][ 'row_id_column' ] );
            $trAttr[ 'class' ][] = $rowId % 2 ? 'odd' : 'even';

            $tdArr = array();
            foreach ( $columns as $colId => $col ) {
                $cellData = $this->cellData( $row, $col );
                $tdArr[ $colId ] = array( 'attr' => $cellData[ 'attr' ], 'html' => $cellData[ 'html' ] );
                if ( !empty( $cellData[ 'row_attr' ] ) ) {
                    $trAttr = array_merge_recursive( $cellData[ 'row_attr' ] );
                }
            }
            $trArr[ $rowId ] = array( 'attr' => $trAttr, 'cells' => $tdArr );
        }

        if ( !empty( $grid[ 'config' ][ 'format_callback' ] ) ) {
            $cb = $grid[ 'config' ][ 'format_callback' ];
            if ( is_callable( $cb ) ) {
                call_user_func( $cb, array( 'grid' => $grid, 'rows' => &$trArr ) );
            } else {
                BDebug::warning( 'Invalid grid format_callback' );
            }
        }

        $trHtmlArr = array();
        foreach ( $trArr as $rowId => $tr ) {
            $tdHtmlArr = array();
            foreach ( $tr[ 'cells' ] as $colId => $cell ) {
                $tdHtmlArr[] = BUtil::tagHtml( 'td', $cell[ 'attr' ], $cell[ 'html' ] );
            }
            $trHtmlArr[] = BUtil::tagHtml( 'tr', $tr[ 'attr' ], join( "\n", $tdHtmlArr ) );
        }

        return join( "\n", $trHtmlArr );
    }

    public function sortClass( $col )
    {
        return '';
    }
}
