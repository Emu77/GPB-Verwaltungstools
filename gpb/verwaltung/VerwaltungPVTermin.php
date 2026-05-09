<?php
require_once '../PVTermin.php';

class VerwaltungPVTermin extends PVTermin {
  static function makeHeaderTr() {
?>
  <tr>
    <th>Wann</th>
    <th>Was</th>
    <th>Betrifft AP1</th>
    <th>Betrifft AP2 / <br />schriftliche Prüfung</th>
    <th>Betrifft mündliche<br />Prüfung / Projekt</th>
    <th></th>
  </tr>
<?php
  }
  
  function makeTr() {
?>
  <tr id="pvtermin_<?= $this->id ?>_tr">
    <td>
      <?= empty($this->beginn) || $this->beginn=='0000-00-00' ? '' : date('d.m.Y',$this->von) ?>
      <?= empty($this->beginn_uhrzeit) || $this->beginn_uhrzeit=='00:00:00' ? '' : date('H:i',$this->von) ?>
      <?= $this->keinEnde ? '' : '-' ?>
      <?= empty($this->ende) || $this->ende=='0000-00-00' ? '' : date('d.m.Y',$this->bis) ?>
      <?= empty($this->ende_uhrzeit) || $this->ende_uhrzeit=='00:00:00' ? '' : date('H:i',$this->bis) ?>
    </td>
    <td><?= $this->was ?></td>
    <td align="center"><?= $this->betrifftAP1 ? '✓' : '' ?></td>
    <td align="center"><?= $this->betrifftAP2 ? '✓' : '' ?></td>
    <td align="center"><?= $this->betrifftMuendliche ? '✓' : '' ?></td>
    <td><a href="javascript:pvtermin_bearbeiten(<?= $this->id ?>)">Bearbeiten</a></td>
  </tr>
  <form action="kurs_pvtermin_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="kursid" value="<?= $this->kursid ?>" />
    <input type="hidden" name="id" value="<?= $this->id ?>" />
  <tr style="display:none;" id="pvtermin_bearbeiten_<?= $this->id ?>_tr">
    <td>
      Von <input type="date" name="beginn" value="<?= $this->beginn ?>" /> <input type="time" name="beginn_uhrzeit" value="<?= $this->beginn_uhrzeit ?>" /><br />
      Bis <input type="date" name="ende" value="<?= $this->ende ?>" /> <input type="time" name="ende_uhrzeit" value="<?= $this->ende_uhrzeit ?>" />
    </td>
    <td><input type="text" name="was" value="<?= htmlentities($this->was,ENT_COMPAT) ?>" style="width:250px;" /></td>
    <td align="center"><input type="checkbox" name="betrifftAP1" value="J" <?= $this->betrifftAP1 ? 'checked' : '' ?> />&nbsp;AP1</td>
    <td align="center"><input type="checkbox" name="betrifftAP2" value="J" <?= $this->betrifftAP2 ? 'checked' : '' ?> />&nbsp;AP2/schriftliche</td>
    <td align="center"><input type="checkbox" name="betrifftMuendliche" value="J" <?= $this->betrifftMuendliche ? 'checked' : '' ?> />&nbsp;mündliche/Projekt/Fachgespräch</td>
    <td><input type="submit" value="Speichern" /> <a href="javascript:pvtermin_bearbeiten_abbrechen()">Abbrechen</a></td>
  </tr>
  </form>
<?php
  }
}
?>