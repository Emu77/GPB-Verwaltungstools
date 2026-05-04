<?php
class Bewertung {
  static $fragen=array(
    'interessant'=>"Die Inhalte waren interessant.",
    'gegliedert'=>"Die Inhalte wurden klar gegliedert.",
    'zielorientiert'=>"Der Unterrichtsaufbau war zielorientiert.",
    'dozent_fachsicher'=>"Der Dozent war fachlich sicher.",
    'dozent_klar'=>"Der Dozent unterrichtete anschaulich und unterstützte das Lernen umfangreich.",
    'material_nuetzlich'=>"Die Unterrichtsmaterialien unterstützten beim Lernen.",
    'material_gut'=>"Die Qualität der Unterrichtsmaterialien war gut.",
    'lernklima'=>"Das Lernklima war angenehm/gelöst.",
    'ausstattung'=>"Die Ausstattung (Räume/ Technik) bzw. die BigBlueButton Organisation war gut."
  );
  static $werte=array(
    1=>"Trifft voll und ganz zu",
    2=>"Trifft überwiegend zu",
    3=>"Trifft eher zu",
    4=>"Trifft eher nicht zu",
    5=>"Trifft überwiegend nicht zu",
    6=>"Trifft überhaupt nicht zu"
  );
  static $farben=array(
    1=>'hsl(120,100%,50%)',
    2=>'hsl(96,100%,50%)',
    3=>'hsl(72,100%,50%)',
    4=>'hsl(48,100%,50%)',
    5=>'hsl(24,100%,50%)',
    6=>'hsl(0,100%,50%)'
  );
}
?>