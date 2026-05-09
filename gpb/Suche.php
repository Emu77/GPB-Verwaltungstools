<?php
class Suche {
  function __construct(...$keys) {
    foreach($keys as $k) {
      $this->$k='';
    }
    $this->where=array();
    $this->values=array();
    $this->types='';
  }
  function makeStringInput($spaltenname,$colspan=1,$width=100,$classname='') {
?>
    <td colspan="<?= $colspan ?>" <?= $classname ? 'class="'.$classname.'"' : '' ?>><input type="text" name="<?= $spaltenname ?>" value="<?= isset($this->$spaltenname) ? htmlentities($this->$spaltenname,ENT_COMPAT) : '' ?>" style="width:<?= $width ?>px;" /></td>
<?php
  }
  function inputString($spaltenname) {
    $this->$spaltenname=isset($_POST[$spaltenname]) ? $_POST[$spaltenname] : (isset($_GET[$spaltenname]) ? $_GET[$spaltenname] : '');
    if(!empty($this->$spaltenname)) {
      $this->addKriterium($spaltenname.' like ?','%'.$this->$spaltenname.'%','s');
    }
  }
  function makeEnumInput($spaltenname,$optionen) {
?>
    <td><select name="<?= $spaltenname ?>" style="max-width:150px;">
      <option value="">Alle</option>
<?php
    foreach($optionen as $o) {
?>
      <option value="<?= $o ?>" <?= isset($this->$spaltenname) && $this->$spaltenname==$o ? 'selected' : '' ?>><?= $o ?></option>
<?php
    }
?>
    </select></td>
<?php
  }
  function makeSelectId($spaltenname,$optionen,$makeOptionText,$kann0=false) {
?>
    <td><select name="<?= $spaltenname ?>" style="max-width:150px;">
      <option value="">Alle</option>
<?php
    if($kann0) {
?>
      <option value="0" <?= isset($this->$spaltenname) && $this->$spaltenname===0 ? 'selected' : '' ?>>Ohne</option>
<?php
    }
    foreach($optionen as $option) {
?>
      <option value="<?= $option->id ?>" <?= isset($this->$spaltenname) && $this->$spaltenname==$option->id ? 'selected' : '' ?>><?= call_user_func($makeOptionText,$option) ?></option>
<?php
    }
?>
    </select></td>
<?php
  }
  function inputInt($spaltenname) {
    $this->$spaltenname=isset($_POST[$spaltenname]) && strlen($_POST[$spaltenname])>0 ? (int)$_POST[$spaltenname] : 
      (isset($_GET[$spaltenname]) && strlen($_GET[$spaltenname])>0 ? (int)$_GET[$spaltenname] : false);
    if($this->$spaltenname!==false) {
      $this->addKriterium($spaltenname.'=?',$this->$spaltenname,'i');
    }
  }
  function makeVonBisInput($colspan=2,$prefix='') {
    $kvon=$prefix.'von';
    $kbis=$prefix.'bis';
?>
    <td colspan="<?= $colspan ?>">
      Zwischen <input type="date" id="<?= $kvon ?>" name="<?= $kvon ?>" value="<?= isset($this->$kvon) ? htmlentities($this->$kvon,ENT_COMPAT) : '' ?>" /><?= $colspan<=1 ? "<br />" : "" ?>
      und <input type="date" id="<?= $kbis ?>" name="<?= $kbis ?>" value="<?= isset($this->$kbis) ? htmlentities($this->$kbis,ENT_COMPAT) : '' ?>" />
    </td>
<?php
  }
  function inputVonBis($beginnspalte,$endespalte,$prefix='') {
    $kvon=$prefix.'von';
    $this->$kvon=isset($_POST[$kvon]) ? $_POST[$kvon] : (isset($_GET[$kvon]) ? $_GET[$kvon] : '');
    if(!empty($this->$kvon)) {
      $this->addKriterium($endespalte.'>=?',$this->$kvon,'s');
    }
    $kbis=$prefix.'bis';
    $this->$kbis=isset($_POST[$kbis]) ? $_POST[$kbis] : (isset($_GET[$kbis]) ? $_GET[$kbis] : '');
    if(!empty($this->$kbis)) {
      $this->addKriterium($beginnspalte.'<=?',$this->$kbis,'s');
    }
  }
  function makeBooleanInput($name,$text) {
?>
    <span class="<?= $name ?>"><input type="checkbox" name="<?= $name ?>" value="J" <?= isset($this->$name) && $this->$name ? 'checked' : '' ?> /><?= $text ?></span>
<?php
  }
  function inputBoolean($name,$where) {
    $this->$name=(isset($_POST[$name]) && $_POST[$name]!='N') || (isset($_GET[$name]) && $_GET[$name]!='N');
    if($this->$name) {
      $this->where[]=$where;
    }
  }
  function addKriterium($where,$value,$type) {
    if(!empty($where)) {
      $this->where[]=$where;
    }
    if($value!==false) {
      $this->values[]=$value;
    }
    if(!empty($type)) {
      $this->types.=$type;
    }
  }
  function makeSubmit($td=true) {
    if($td) {
?>
    <td>
<?php
    }
?>
     <input type="submit" value="Suchen" />
<?php
    if($td) {
?>
    </td>
<?php
    }
  }
  function prepare($sqlVorWhere,$sqlNachWhere) {
    global $db;
    $stmt=$db->prepare($sqlVorWhere." ".implode(' and ',$this->where)." ".$sqlNachWhere);
    if(!empty($this->types)) {
      $stmt->bind_param($this->types,...$this->values);
    }
    return $stmt;
  }
}

class RaumplanSuche extends Suche {
  function __construct() {
    parent::__construct('am','ort');
    $this->ferienwhere=array();
  }
  function inputString($spaltenname) {
    parent::inputString($spaltenname);
    if($spaltenname=='ort' && !empty($this->$spaltenname)) {
      $this->ferienwhere[count($this->ferienwhere)-1]="((art<>'Institutsferien' and art<>'Klassenferien') or ort like ?)";
    }
  }
  function addKriterium($where,$value,$type) {
    parent::addKriterium($where,$value,$type);
    if(!empty($where)) {
      $this->ferienwhere[]=$where;
    }
  }
  function prepareFerien() {
    global $db;
    $stmt=$db->prepare("select * from gpb_ferien where ".implode(' and ',$this->ferienwhere)." order by art,ort");
    if(!empty($this->types)) {
      $stmt->bind_param($this->types,...$this->values);
    }
    return $stmt;
  }
}
?>