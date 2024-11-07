<?php 

function containerFmtFunc_person($pagename,$content,&$config) {
  global $HTMLStylesFmt,$PubDirUrl,$Containers_PubDirUrl,$UploadUrlFmt,$UploadPrefixFmt,$Containers_dbGroup;

  $config['style']['height'] = "150px";
  $config['style']['width'] = "305px";
  $config['style']['margin'] = "7px";
  $config['style']['padding'] = "6px";

  $img_rootpath="$Containers_PubDirUrl/personfmt"; 
  $img_placeholder="unknown.png";
  $icons=array("home" => "Homepage","scholar" => "Google Scholar","dblp" => "dblp");  // icon image is {$key}.svg at $img_rootpath


  $pattern='/^\s*:\s*(\w+)\s*:[^\S\r\n]*[\'"]*([^\r\n]*?)[\'"]*\s*$/m';
  $result=preg_match_all($pattern,$content,$matches, PREG_SET_ORDER);
  
  $person=array();
  foreach ($matches as $match) {
     $person[$match[1]]=$match[2];     
  }

  # if person is looked up in container database, but id/gid could not be found
  $prefix="not_existing_id";
  if ( substr( $content, 0, strlen($prefix) ) === $prefix  ) {
    $person["title"]="db missing";
    $pieces = explode("=", $content);
    $missing_id=$pieces[1];
    $person["name"]="person id '$missing_id'";
  }
  $prefix="not_existing_gid";
  if ( substr( $content, 0, strlen($prefix) ) === $prefix  ) {
    $person["title"]="db missing";
    $pieces = explode("=", $content);
    $missing_id=$pieces[1];
    $person["name"]="group id '$missing_id'";
  }

  $links=array();
  # loop over icons in order as defined above 
  foreach ($icons as $key => $value ) {
     if ( array_key_exists($key,$person) ) {
        if (! Empty($person[$key]) )  {
           $links[] = "      <a href=\"{$person[$key]}\" target=\"_blank\"><img class=\"icon\" src=\"{$img_rootpath}/{$key}.svg\" title=\"{$value}\"/></a>";
        }   
     }
  }

  if ( $person["image"] == '' ) {
     $image_url = "$img_rootpath/$img_placeholder";
  } else {
    if (substr( $person["image"] , 0 , 1) == "/") {
        // gives relative path within wiki url structure
        $image_url = $PubDirUrl . "/" . $person["image"];
    } elseif ( substr( $person["image"] , 0 , 4 ) === 'http' ) {    
        // gives absolute url 
        $image_url = $person["image"];
    } else {
        if ($config['referred']) {
            $uploadUrl = FmtPageName("$UploadUrlFmt", $pagename);
            $image_url =  $uploadUrl . "/" . $Containers_dbGroup . "/" . $person["image"];
        } else {
            $uploadUrl = FmtPageName("$UploadUrlFmt$UploadPrefixFmt", $pagename);
            $image_url = $uploadUrl . "/" . $person["image"];
        }
    }
  }
  
  $links=implode("\n",$links);
  $output= <<<"EOF"
  <img class="left" src="{$image_url}" alt="Image">
  <div class="right" >
      <div class="title">{$person["title"]}</div>
      <div class="name">{$person["name"]}</div>
      <div class="function">{$person["function"]}</div>
      <div class="links">
  {$links} 
      </div>
  </div>
  EOF;

  $HTMLStylesFmt['containersPerson'] = <<<'EOT'
  .container > .content {  
 
    /* in content div we have padding around inner-content */
    /* the inner content height (set in --height) is the content height without the padding */
    --height: calc( var(--content-height) - 2 * var(--content-padding) );  /* 170px - 2*10px = 150px */
    /* the inner content width (set in --width) is the content width without the padding */
    --width: calc( var(--content-width) - 2 * var(--content-padding) );    /* 380px -2*10px = 360px  */
    
    /* in content we have: left and right divs */
    /* which get widths: */     
    --left-width:  min( var(--height) , 150px );  
    --right-width: calc(  var(--width) - var(--left-width) -  var(--content-padding) ); /* 360px -150px -10px = 200px */
    /* note: right side gets remaining width of content width; note: left and right divs are padded within content */
    /* picture is square */
    --left-height: var(--left-width);
  }  

  .left {
      width: var(--left-width);
      height:  var(--left-height);
      position: absolute;
      left:var(--content-padding);
      top:var(--content-padding);
      padding: 0;
      margin: 0;
  }

  .right   {
      width: var(--right-width);
      height: var(--height);
      position: absolute;
      left:calc( var(--left-width) + 2 * var(--content-padding));
      top:var(--content-padding);    
      padding: 0;
      margin: 0;  
      
      /*  assume only overflow in y direction*/     
      overflow-x:hidden;
      overflow-y:auto;  
  }
  .title {
      text-align: left;
  }
  .name {
      text-align: left;
      font-weight: bold;
  }
  .function {
      text-align: left;
  }
  .links {
      text-align: left; 
      position: absolute;
      bottom: 0;
      right: 0; 
      overflow-y:hidden; // in edge with only one icon it somehow causes overflow??
  }
  .icon {
      height: 20px;
      width: 20px;
    /*  filter: grayscale(100%);*/
  }
  EOT;

  $config['containsMarkdown'] = False;
  return $output;
}


$Containers_FormatterFunctions[] = "containerFmtFunc_person";