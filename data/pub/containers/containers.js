function getCssValue(elm,name) {
    cstyle = getComputedStyle(elm);
    val_str=cstyle.getPropertyValue(name);
    val=parseInt( val_str.replace(/\s*px\s*$/,"") );
    return val;
}  
function fixWidth(elm){
    column_width = getCssValue(elm,"column-width");
    
    //console.log('column_width:' + column_width);  
    elm.style.width="100%";
    current_width = getCssValue(elm,"width");
    //console.log('current_width: ' + current_width);
    new_width = Math.trunc(current_width/column_width)*column_width;
    new_width_str=new_width + "px";
    //console.log('new_width_str: ' + new_width_str);
    elm.style.width=new_width_str;
};
function fixWidthAll() {
    lst=document.querySelectorAll('.containerColumns');
    lst.forEach(fixWidth);    
};
window.addEventListener("resize", fixWidthAll);
document.addEventListener('DOMContentLoaded', () => { fixWidthAll(); });

function toggleStyle(elm) {
    if (elm.classList.contains('containerRows')) {
        elm.classList.add("containerColumns");
        elm.classList.remove("containerRows");
    } else {
        elm.classList.remove("containerColumns");
        elm.classList.add("containerRows"); 
        // note for columns mode we fix containers width after each resize with javascript,
        // however for rows mode we want its width again to be auto so that browser does resize it.
       // elm.style.width="auto";   
        elm.style.width="auto";    
    }  
}    
function toggleStyleContainers() {
    collst=document.querySelectorAll('.containerColumns');        
    rowlst=document.querySelectorAll('.containerRows');
    collst.forEach(toggleStyle);
    rowlst.forEach(toggleStyle);  
    fixWidthAll(); 
};
