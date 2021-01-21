<?php
/*
 * Plugin Name: Paulund WP List Table Example
 * Description: An example of how to use the WP_List_Table class to display data in your WordPress Admin area
 * Plugin URI: http://www.paulund.co.uk
 * Author: Paul Underwood
 * Author URI: http://www.paulund.co.uk
 * Version: 1.0
 * License: GPL2
 */

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class QW_List_Table extends WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items($QBdata = null, $perPage = 2)
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data($QBdata);
        usort( $data, array( &$this, 'sort_data' ) );

        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'id'                => 'ID',
            'title'      => 'Project Name',
            'priority'          => 'Priority',
            'status'            => 'Project Status',
            'bids'              => 'List of Bids',
            'start_date'        => 'Start Date',
            'end_date'          => 'End Date',
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {   
        $array = array(
            'id' => array('id', false),
            'title' => array('title', false)
        );
        return $array;
    }

     /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data($QBdata)
    {
        $data = array();

        if($QBdata){
            foreach($QBdata->record as $keys => $record){

                $data[] = array(
                    'id'                => $record['rid'],
                    'title'      => $record->project_name,
                    'priority'          => $record->priority,
                    'status'            => $record->project_status,
                    'bids'              => $record->list_of_bids,
                    'start_date'        => 'start',
                    'end_date'          => 'end',

                );


              // foreach($record as $k => $v){
              //   $this->print_data($k);
              // }
            }
        }

        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'id':
            case 'title':
            case 'priority':
            case 'status':
            case 'bids':
            case 'start_date':
            case 'end_date':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'title';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }


        $result = strcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }


    public function column_title( $item ) {

        $page = wp_unslash( $_REQUEST['page'] ); // WPCS: Input var ok.

        // Build edit row action.
        $edit_query_args = array(
            'page'   => $page,
            'action' => 'edit',
            'movie'  => $item['id'],
        );

        $actions['edit'] = sprintf(
            '<a href="%1$s">%2$s</a>',
            esc_url( wp_nonce_url( add_query_arg( $edit_query_args, 'admin.php' ), 'editmovie_' . $item['id'] ) ),
            _x( 'Edit', 'List table row action', 'wp-list-table-example' )
        );

        // Build delete row action.
        $delete_query_args = array(
            'page'   => $page,
            'action' => 'delete',
            'movie'  => $item['id'],
        );

        $actions['delete'] = sprintf(
            '<a href="%1$s">%2$s</a>',
            esc_url( wp_nonce_url( add_query_arg( $delete_query_args, 'admin.php' ), 'deletemovie_' . $item['id'] ) ),
            _x( 'Delete', 'List table row action', 'wp-list-table-example' )
        );

        // Return the title contents.
        return sprintf( '%1$s <span style="color:silver;"></span>%3$s',
            $item['title'],
            $item['id'],
            $this->row_actions( $actions )
        );
    }
}
?>