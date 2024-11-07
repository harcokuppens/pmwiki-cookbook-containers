<?php if (!defined('PmWiki')) exit();

function readMarkupFromPage($pagename) {
    // ReadPage returns null if page does not exist
    $markup=ReadPage($pagename, READPAGE_CURRENT)['text'];
    return $markup;
};


function getSmallestNoneNull($a,$b) {
    if (is_null($a) && is_null($b)) {
        return 0;
    } elseif ( is_null($a) ) {
        return 2;
    } elseif (is_null($b) ) {
        return 1;
    } elseif ( $a <  $b) {
        return 1;
    } else {
        return 2;
    }
}
function parseMarkupForContainersWithId($page,$markup,&$id2container,&$gid2ids,&$id2page,&$gid2page) {
    $errormsg="";

    // regex building blocks
    $optional_options='(?:\s+([^\n]*?))?';
    $container='\(:container' . $optional_options . ':\)';
    $containers='\(:containers' . $optional_options . ':\)';
    $containerend='\(:containerend:\)';
    $containersend='\(:containersend:\)';
    $containersgroup='\(:containersgroup' . $optional_options . ':\)';
    // regexs used
    $regex_containers='/' . $containers . '(.*?)' . $containersend .'/si';
    $regex_container='/' . $container . '(.*?)' . $containerend .'/si';
    // note: in source use cid and gcid;  because id has special meaning for containers for css style
    $regex_id='/cid="?(\w+)"?/i';
    $regex_gid='/cgid="?(\w+)"?/i';
    $regex_containerref='/\(:containerref' . $optional_options . ':\)/si';

    # match (:containers:) directive
    preg_match_all($regex_containers, $markup, $csmatches, PREG_SET_ORDER, 0);
    foreach ($csmatches as $csmatch) {
        $content_of_containers=$csmatch[2];
        $optional_params=$csmatch[1];
        $found=preg_match($regex_gid,$optional_params,$gidmatches);
        $gid="";
        if ( $found ) {
            $gid=$gidmatches[1];  
            $gid2ids[$gid]=array();
            if (array_key_exists($gid,$gid2page)) {
                $otherpage=$gid2page[$gid];
                if ( $otherpage == $page ) {
                    $errormsg="none unique cgid: cgid '$gid' is defined twice in page '$page'";
                } else {
                    $errormsg="none unique cgid: cgid '$gid' is defined twice in pages '$page' and '$otherpage'";
                }  
                return $errormsg;   
            } else {     
                $gid2page[$gid]=$page;
            }
        }  

        // parse within containers directive for first matching container or containerref directive 
        // and add them to the database and to the containers group (if exist) in order of finding them
        while (1) {
            // match both container and containerref, the one which is found first is handled
            preg_match($regex_container, $content_of_containers, $cmatches, PREG_OFFSET_CAPTURE);
            $offset_container=$cmatches[0][1];
            preg_match($regex_containerref, $content_of_containers, $crmatches, PREG_OFFSET_CAPTURE);
            $offset_containerref=$crmatches[0][1];
            $result=getSmallestNoneNull($offset_container,$offset_containerref);
            if ($result == 0 ) break; // no container nor containerref found (anymore)
            if ($result == 1 ) {
                # first match is container
                $whole_container_directive=$cmatches[0][0];
                $optional_params=$cmatches[1][0];
                $container_contents=$cmatches[2][0];
                $offset_after_container_directive=$offset_container+strlen($whole_container_directive);
                $content_of_containers=substr($content_of_containers,$offset_after_container_directive);
                $found=preg_match($regex_id,$optional_params,$idmatches);
                $id="";
                if ( $found ) {
                    $id=$idmatches[1];
                    if (array_key_exists($id,$id2page)) {
                        $otherpage=$id2page[$id];
                        if ( $otherpage == $page ) {
                            $errormsg="none unique cid: cid '$id' is defined twice in page '$page'";
                        } else {
                            $errormsg="none unique cid: cid '$id' is defined twice in pages '$page' and '$otherpage'";
                        }      
                        return $errormsg;   
                    } else {
                        $id2page[$id]=$page;
                    }
                    $id2container[$id]=$container_contents;
                    if ($gid) {
                        $gid2ids[$gid][]=$id; 
                    }    
                }
            } else {
                # first match is containerref
                $whole_containerref_directive=$crmatches[0][0];
                $optional_params=$crmatches[1][0];
                #$containerref_contents=$crmatches[2][0];
                $offset_after_containerref_directive=$offset_containerref+strlen($whole_containerref_directive);
                $content_of_containers=substr($content_of_containers,$offset_after_containerref_directive);
                if ($gid) {
                    $id="";
                    $found=preg_match($regex_id,$optional_params,$idmatches);
                    if ( $found ) {
                        # add found refered id to group
                        $id=$idmatches[1];
                        $gid2ids[$gid][]=$id; 
                    }
                }
                
            }
        }
    }
    return $errormsg;
}

function parseMarkupForContainersgroups($page,$markup,&$gid2ids,&$gid2page) {

    $errormsg="";

    // regex building blocks
    $optional_options='(?:\s+([^\n]*?))?';
    $containersgroup='\(:containersgroup' . $optional_options . ':\)';
    // regexs used
    $regex_containersgroup="/$containersgroup/si";
    $regex_gid='/cgid="?(\w+)"?/i';
    $regex_memberids='/memberids=["\']([\w\s,]*)[\'"]/i';
    $regex_membergids='/membergids=["\']([\w\s,]*)[\'"]/i';

    preg_match_all($regex_containersgroup, $markup, $matches, PREG_SET_ORDER, 0);
    foreach ($matches as $match) {
        # even gid is optional; but then (:containersgroup :) does nothing!
        $optional_params=$match[1];
        $found=preg_match($regex_gid,$optional_params,$gidmatches);
        if (  $found ) {
            $gid=$gidmatches[1];  
            $gid2ids[$gid]=array();
            if (array_key_exists($gid,$gid2page)) {
                $otherpage=$gid2page[$gid];
                if ( $otherpage == $page ) {
                    $errormsg="none unique cgid: cgid '$gid' is defined twice in page '$page'";
                } else {
                    $errormsg="none unique cgid: cgid '$gid' is defined twice in pages '$page' and '$otherpage'";
                }  
                return $errormsg;   
            } else {     
                $gid2page[$gid]=$page;
            }

            # add to group gid the ids in its membergids  
            $found=preg_match($regex_membergids,$optional_params,$gidmatches);
            if (  $found ) {      
                $memgids_str=$gidmatches[1];
                $memgids_str_only_commasep = str_replace(' ', '', $memgids_str);
                $memgids_str_only_singlecommasep = preg_replace("/,+/", ",", $memgids_str_only_commasep);
                if ( $memgids_str_only_singlecommasep!=""  ) { 
                    $memgids = explode(",", $memgids_str_only_singlecommasep);
                    foreach ($memgids as $membergid){
                        if ( $membergid == "") continue;
                        # add ids from group '$membergid' to group '$gid'
                        if (array_key_exists($membergid,$gid2ids)) {
                            $merge = array_merge($gid2ids[$gid],$gid2ids[$membergid]); 
                            $gid2ids[$gid]=$merge;
                        }    
                    }
                }    
            }

            # add to group gid its memberids  
            $found=preg_match($regex_memberids,$optional_params,$idmatches);
            if (  $found ) {      
                $memids_str=$idmatches[1];
                $memids_str_only_commasep = str_replace(' ', '', $memids_str);
                $memids_str_only_singlecommasep = preg_replace("/,+/", ",", $memids_str_only_commasep);
                if ($memids_str_only_singlecommasep!="") { 
                    $memids = explode(",", $memids_str_only_singlecommasep);
                    foreach ($memids as $memberid){
                        if ( $memberid != "") $gid2ids[$gid][]=$memberid;
                    }
                }    
            }
            $gid2ids[$gid]=array_unique($gid2ids[$gid]);
        }  
    }
    return $errormsg;
}

function parseContainersDb($pages,&$id2container,&$gid2ids,$pagename=null,$editing=false,$editdata=null) {
    # error if id not unique 
    $id2page=array(); 
    $gid2page=array(); 
    $id2container=array();
    $errormsg="";
    foreach ($pages as $page){
        if ( $editing && !empty($pagename) && $page == $pagename ) {
            $markup=$editdata;
        } else {
            $markup=readMarkupFromPage($page);
        }
        if( empty($markup) ) continue;
        $errormsg=parseMarkupForContainersWithId($page,$markup,$id2container,$gid2ids,$id2page,$gid2page);
        if ($errormsg) return $errormsg;
    }
    foreach ($pages as $page){
        if ( $editing &&  !empty($pagename) && $page == $pagename ) {
            $markup=$editdata;
        } else {
            $markup=readMarkupFromPage($page);
        }
        if( empty($markup) ) continue; 
        $errormsg=parseMarkupForContainersgroups($page,$markup,$gid2ids,$gid2page);
        if ($errormsg) return $errormsg;
    }
    return $errormsg;
}

//function readDb($pagename,&$id2container,&$gid2ids) {
function readDb($pagename,$editing=false,$data=null) {    
    global $FarmD,$dbLoaded,$dbpages, $id2container, $gid2ids,$GroupHeaderFmt,$HTMLStylesFmt,$Containers_dbGroup,$Containers_dbExcludePages;
    
    // only read db once
    if ($dbLoaded) return ""; // no error message; if loading db failed we wouldn't get here.
    $dbLoaded=true;

    // read db
    $id2container=array();
    $gid2ids=array();
    
    // set dbpages to all pages in the database group $Containers_dbGroup
    // we exclude the given exceptions in $Containers_dbExcludePages
    if (! empty($Containers_dbGroup)){
        $dbpages=array();
        $pagesDir="$FarmD/wiki.d";
        foreach (glob("$pagesDir/{$Containers_dbGroup}.*") as $filepath) {
            $group_dir="$pagesDir/{$Containers_dbGroup}.";
            $filename=substr_replace($filepath,"",0,strlen($group_dir));
            if ( in_array($filename,$Containers_dbExcludePages) ) continue;
            $dbpages[]="{$Containers_dbGroup}.{$filename}";
        }
    }

    # add current page also because there you can locally define db items for local usage
    $dbpages[]=$pagename;  # note creates dbpages error if not yet created!
    $dbpages=array_unique($dbpages);
    $errormsg=parseContainersDb($dbpages,$id2container,$gid2ids,$pagename,$editing,$data);

    // when editing then ValidateDbSource function does display error in edit form and prevents saving page
    // when not editing display error message of parsing db at top of page 
    if ($errormsg && !$editing)  {
        $GroupHeaderFmt=$GroupHeaderFmt . "\n\n<p class='editerror'>ERROR: Parse error container db:<br> ".print_r($errormsg,true)."</p>\n";
        $HTMLStylesFmt['containers'] = ".editerror { color:red; font-style:italic; margin-top:1.33em; margin-bottom:1.33em; }\n";
    }
    return $errormsg;
}

$dbLoaded=false;


# when editing (edit action): 
#   - validate edited source is correct. If not the save is rejected with error message.
#   - replace the standard preview function with our special variant for Bibtex
include_once("$FarmD/scripts/simuledit.php");
array_unshift($EditFunctions,'ValidateDbSource');


function ValidateDbSource($pagename, &$page, &$new) {
    global $EnablePost,$MessagesFmt,$HTMLStylesFmt;
  
    # EnablePost only set when pressed 'Save' or 'Save and edit' button
    # We do not validate source when not saving page.
    if ( !$EnablePost  )  return true;

    $data= $new['text'];

    # We cancel save if database contains errors.
    # -> we read database with new editing data for pagename (do not read old data for page from disk)
    $editing=true;
    $errorText=readDb($pagename,$editing,$data);
    if ($errorText) {
        $EnablePost = 0;
        $MessagesFmt['errorvalidate'] = "<br><b> Page is not saved. </b> <p class='editerror'>ERROR: $errorText</p>\n";
        $HTMLStylesFmt['containers'] = ".editerror { color:red; font-style:italic; margin-top:1.33em; margin-bottom:1.33em; }\n";
    }
}


function getGroup($gid) {
    global $gid2ids;
    // assume db already read when calling this function
    $ids=array();
    if (!empty($gid2ids) && array_key_exists($gid,$gid2ids)) {
       $ids=$gid2ids[$gid];
    }  else {
       $ids[]="not_existing_gid=$gid";
    } 
    return $ids;
}     
function getContainer($id) {
    global $id2container; 
    // assume db already read when calling this function
    if (!empty($id2container) && array_key_exists($id,$id2container)) {
       $content=$id2container[$id];
    }  else  {
        $prefix="not_existing_gid=";
        if ( substr( $id, 0, strlen($prefix) )  === $prefix ){
            $content=$id;
        } else{
            $content="not_existing_id=$id";
        }
    }
    return $content;
}  

