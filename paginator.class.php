<?php
class paginator {


    private $_limit;
    private $_num;
    private $_query;
    private $_total;
    private $_offset;

    public function getQuery($query, $total)
    {
          $this->_query = $query;
          $this->_total = $total;
    }

    public function getData( $limit = 10, $num = 1, $offset ) {

        $this->_limit   = $limit;
        $this->_num    = $num;
        $this->_offset = $offset;

        if ( $this->_limit == 'all' ) {
            $query      = $this->_query;
        } else {
            $query      = $this->_query . " LIMIT " . ( ( $this->_num - 1 ) * $this->_limit ) . ", $this->_limit";
        }
        $rs             = engine::DB()->getAll( $query );


        if ($rs) {
            $row = array_keys($rs[0]);
            foreach ($rs as $rows) {
                $results[] = $rows;
            }
        }

        $result         = new stdClass();
        $result->num   = $this->_num;
        $result->limit  = $this->_limit;
        $result->total  = $this->_total;
        $result->offset = $this->_offset;
        $result->data   = @$results;

        return $result;
    }

}