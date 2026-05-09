<?php
class Liste {
  function __construct($className,$stmt=null) {
    $this->className=$className;
    $this->byId=array();
    $this->alle=array();
    if($stmt) {
      $this->laden($stmt);
    }
  }
  function add($row) {
    $this->byId[$row->id]=$row;
    $this->alle[]=$row;
  }
  function laden($stmt) {
    $stmt->execute();
    $result=$stmt->get_result();
    while($row=$result->fetch_object($this->className)) {
      $row->init();
      $this->add($row);
    }
    $result->free();
  }
}
?>