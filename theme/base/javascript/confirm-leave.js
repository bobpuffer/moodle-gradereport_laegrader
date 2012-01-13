function promptSave() {
var ulArray = document.getElementsByTagName('ul');
for (i = 0; i < 3; i++) {
var ulId = ulArray[i].id;
var ulClass = ulArray[i].className;
if (ulId.match('draftfiles-') == 'draftfiles-') {
if ((ulClass.match('fm-filelist') != 'fm-filelist') && (confirmLeave.state != "clean")) {
return "If you leave the page now, changes to your files will not be saved."; //In some browsers a standard message will be returned
}
}
}
}

function markClean() {
confirmLeave.state = "clean";
}

confirmLeave = new Object();

var fileForm;
fileForm = document.getElementById('mform1');
fileForm.onsubmit = function(){markClean();};

window.onbeforeunload = promptSave; 
