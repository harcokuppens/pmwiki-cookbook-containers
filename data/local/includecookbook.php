<?php

@include_once("$FarmD/cookbook/containers/containers.php");


@include_once("$FarmD/cookbook/containers/personfmt/personfmt.php");
// $Containers_FormatterFunctions[] = "containerFmtFunc_person";
#$Containers_ToggleButtonConfig['style']='';
array_push($WikiLibDirs, new PageStore('$FarmD/cookbook/containers/wiki.d/{$FullName}'));  // lets you open page 'ContainersExample'


@include_once("$FarmD/cookbook/imagepopup/imagepopup.php");

