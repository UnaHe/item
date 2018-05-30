/*jslint browser: true, newcap: false, white: false, onevar: false, plusplus: false, eqeqeq: false, nomen: false */
/*global window:false, $:false, imgmap:false, air:false */

/** GLOBALS SECTION ***********************************************************/

var myimgmap, props, outputmode = 'imagemap', imgroot, lastClickId;
var staticBaseUrl = 'https://signposs1.oss-cn-shenzhen.aliyuncs.com/';

/** FUNCTION SECTION **********************************************************/

function gui_toggleMore() {
    var parent = $('#more_actions').parent().find('.toggler');
    $('#more_actions').css({
        top: $(parent).offset().top + ($(parent).outerHeight()),
        left: $(parent).offset().left
    });

    $('#more_actions').slideToggle(200, function () {
        if ($(this).css('display') == 'none') {
            $(parent).addClass('toggler_off');
        }
        else {
            $(parent).removeClass('toggler_off');
        }
    });
}

function gui_colorChanged(obj) {
    myimgmap.pic_container.style.backgroundColor = obj.value;
    gui_toggleMore();
}


/**
 *    Handles mouseover on props row.
 */
function gui_row_mouseover(e) {
    if (myimgmap.is_drawing) {
        return;
    }//exit if in drawing state
    if (myimgmap.viewmode === 1) {
        return;
    }//exit if preview mode
    var obj = (myimgmap.isMSIE) ? window.event.srcElement : e.currentTarget;
    if (typeof obj.aid == 'undefined') {
        obj = obj.parentNode;
    }
    //console.log(obj.aid);
    myimgmap.highlightArea(obj.aid);
}

/**
 *    Handles mouseout on props row.
 */
function gui_row_mouseout(e) {
    if (myimgmap.is_drawing) {
        return;
    }//exit if in drawing state
    if (myimgmap.viewmode === 1) {
        return;
    }//exit if preview mode
    var obj = (myimgmap.isMSIE) ? window.event.srcElement : e.currentTarget;
    if (typeof obj.aid == 'undefined') {
        obj = obj.parentNode;
    }
    myimgmap.blurArea(obj.aid);
}

/**
 *    Handles click on props row.
 */
function gui_row_click(e) {
    if (myimgmap.viewmode === 1) {
        return;
    }//exit if preview mode
    var obj = (myimgmap.isMSIE) ? window.event.srcElement : e.currentTarget;
    //var multiple = (e.originalTarget.name == 'img_active');
    //myimgmap.log(e.originalTarget);

    if (typeof obj.aid == 'undefined') {
        obj = obj.parentNode;
    }
    if (obj.aid === lastClickId) {
        return;
    }
    lastClickId = obj.aid;
    var areaId;
    for (var i in obj.attributes) {
        if (obj.attributes[i].name === 'data-id') {
            areaId = obj.attributes[i].value;
            break;
        }
    }
    // console.dir(obj.attributes[2].value);
    //gui_row_select(obj.aid, false, multiple);
    gui_row_select(obj.aid, false, false);
    myimgmap.currentid = obj.aid;
    $('#areaTypeInput').show();
    // $('#areaTypeInput'+this.value).show();
    $('#clickAreaNumber').html(obj.aid + ' 号区域');
    if (areaId !== undefined) {
        setAreaValue(areaId);
    } else {
        $('#areaTypeSelect').val('').multiselect('rebuild');
        $('#musicFilePreview1').html('');
        $('#musicFile1,#content1').val('');
        $('.areaTypeInput').hide();
    }
    // setAreaValue(obj.attributes[2].value);
    // $('#clickAreaInformationTitle').html(this[this.value].text);
}

function setAreaValue(areaId) {
    if (areaListJson[areaId] !== undefined) {
        var _data = areaListJson[areaId];
        $('.areaTypeInput').hide();
        $('#musicFilePreview1').html('');
        $('#content1').val('');
        $('#musicFilePreview2').html('');
        $('#screenImagePreview2').html('');
        $('#videoFilePreview3').html('');
        postData['equipment_infrared_area_id'] = areaId;
        switch (_data.equipment_infrared_area_type) {
            case '1':
                $('#areaTypeSelect').val(_data.equipment_infrared_area_type).multiselect('rebuild');
                if (_data.equipment_infrared_area_file !== undefined && _data.equipment_infrared_area_file !== '') {
                    var _file = (_data.equipment_infrared_area_file_source == "oss" ? 'https://signposs1.oss-cn-shenzhen.aliyuncs.com/' : '/') + _data.equipment_infrared_area_file;
                    $('#musicFilePreview1').html('<audio src="' + _file + '" controls preload="auto" data-url="' + _data.equipment_infrared_area_file + '"> </audio> <button type="button" class="btn btn-danger btn-xs" style="margin-left: 10px;margin-bottom: 20px;" onclick="removeFile(\'' + _file + '\',\'musicFilePreview1\')">X</button>');
                } else {
                    $('#musicFilePreview1').html('');
                }
                $('#content1').val(_data.equipment_infrared_area_content);
                break;
            case '2':
                $('#areaTypeSelect').val(_data.equipment_infrared_area_type).multiselect('rebuild');
                if (_data.equipment_infrared_area_file !== undefined && _data.equipment_infrared_area_file !== '') {
                    var _file = (_data.equipment_infrared_area_file_source == "oss" ? 'https://signposs1.oss-cn-shenzhen.aliyuncs.com/' : '/') + _data.equipment_infrared_area_file;
                    $('#musicFilePreview2').html('<audio src="' + _file + '" controls preload="auto" data-url="' + _data.equipment_infrared_area_file + '"> </audio> <button type="button" class="btn btn-danger btn-xs" style="margin-left: 10px;margin-bottom: 20px;" onclick="removeFile(\'' + _data.equipment_infrared_area_file + '\',\'musicFile\',\'2\')">X</button>');
                } else {
                    $('#musicFilePreview2').html('');
                }
                var _content = _data.equipment_infrared_area_content.split(',');
                var content2Html = '';
                for (var i = 0; i < _content.length; i++) {
                    var idName = 'screenImage_' + _content[i].replace(/[\/|.]/g, '');
                    content2Html += '<div style="float: left;margin-right: 20px;border:1px dashed #ccc;" id="' + idName + '"><p align="center"><button type="button" class="btn btn-danger btn-xs" style="margin-left: 30px;" onclick="removeFile(\'' + _content[i] + '\',\'' + idName + '\',\'' + _data.equipment_infrared_area_type + '\')">X</button></p><a href="' + staticBaseUrl + _content[i] + '" target="_blank"><img src="' + staticBaseUrl + _content[i] + '" width="60" data-url="' + _content[i] + '"/></a></div>';
                }
                $('#screenImagePreview2').html(content2Html);
                break;
            case '3':
                $('#areaTypeSelect').val(_data.equipment_infrared_area_type).multiselect('rebuild');
                if (_data.equipment_infrared_area_file !== undefined && _data.equipment_infrared_area_file !== '') {
                    var _file = (_data.equipment_infrared_area_file_source == "oss" ? 'https://signposs1.oss-cn-shenzhen.aliyuncs.com/' : '/') + _data.equipment_infrared_area_file;
                    $('#videoFilePreview3').html('<video src="' + _file + '" width="90%" controls preload data-url="' + _data.equipment_infrared_area_file + '">您的浏览器不支持播放。</video> <button type="button" class="btn btn-danger btn-xs" style="margin-left: 10px;margin-bottom: 20px;" onclick="removeFile(\'' + _data.equipment_infrared_area_file + '\',\'videoFile\',\'3\')">X</button>');
                } else {
                    $('#videoFilePreview3').html('');
                }
                break;
            case '4':
                $('#areaTypeSelect').val(_data.equipment_infrared_area_type).multiselect('rebuild');
                var _content = _data.equipment_infrared_area_content.split('|');
                $('#mapId').val(_content[0]).multiselect('rebuild');
                $.ajax('/polygon/ajaxgetlistbymapid', {
                    data: {mapId:_content[0]},
                    type: 'post',
                    dataType: 'json',
                    success: function (data) {
                        var mapGidHtml = '<option value="">&lt;选择位置&gt;</option>';
                        if (data.code === 0) {
                            for (var i=0;i<data.data.length;i++){
                                var selected = '';
                                if (data.data[i].map_gid==_content[1]) selected=' selected';
                                mapGidHtml += '<option value="'+data.data[i].map_gid+'"'+selected+'>'+data.data[i].name+'</option>';
                            }
                        }else{
                            alert(data.msg);
                        }
                        $('#mapGid').html(mapGidHtml).multiselect('rebuild');
                    },
                    error: function () {
                    }
                });
                break;
            case '5':
                $('#areaTypeSelect').val(_data.equipment_infrared_area_type).multiselect('rebuild');
                break;
        }
        $('#deleteBtn'+_data.equipment_infrared_area_type).attr('data-id' , areaId);
        $('#areaTypeInput'+_data.equipment_infrared_area_type).show();
        $('#areaTypeInput').show();
    }
}

/**
 *    Handles click on a property row.
 *    @author    Adam Maschek (adam.maschek(at)gmail.com)
 *    @date    2006-06-06 16:55:29
 */
function gui_row_select(id, setfocus, multiple) {
    if (myimgmap.is_drawing) {
        return;
    }//exit if in drawing state
    if (myimgmap.viewmode === 1) {
        return;
    }//exit if preview mode
    if (!document.getElementById('img_active_' + id)) {
        return;
    }
    //if (!multiple)
    gui_cb_unselect_all();
    document.getElementById('img_active_' + id).checked = 1;
    if (setfocus) {
        document.getElementById('img_active_' + id).focus();
    }
    //remove all background styles
    for (var i = 0; i < props.length; i++) {
        if (props[i]) {
            props[i].style.background = '';
        }
    }
    //put highlight on actual props row
    props[id].style.background = '#e7e7e7';
    $('#areaTypeInput').show();
    $('#clickAreaNumber').html(id + ' 号区域');
}

/**
 *    Handles delete keypress when focus is on the leading checkbox/radio.
 *    @author    adam
 */
function gui_cb_keydown(e) {
    if (myimgmap.viewmode === 1) {
        return;
    }//exit if preview mode
    var key = (myimgmap.isMSIE) ? event.keyCode : e.keyCode;
    //alert(key);
    if (key == 46) {
        //delete pressed
        myimgmap.removeArea(myimgmap.currentid);
    }
}

/**
 *    Unchecks all checboxes/radios.
 */
function gui_cb_unselect_all() {
    for (var i = 0; i < props.length; i++) {
        if (props[i]) {
            document.getElementById('img_active_' + i).checked = false;
        }
    }
}

/**
 *    Handles arrow keys on img_coords input field.
 *    Changes the coordinate values by +/- 1 and updates the corresponding canvas area.
 *    @author    adam
 *    @date    25-09-2007 17:12:43
 */
function gui_coords_keydown(e) {
    if (myimgmap.viewmode === 1) {
        return;
    }//exit if preview mode
    var key = (myimgmap.isMSIE || myimgmap.isOpera) ? event.keyCode : e.keyCode;
    var obj = (myimgmap.isMSIE || myimgmap.isOpera) ? window.event.srcElement : e.originalTarget;
    //obj is the input field
    //this.log(key);
    //this.log(obj);
    if (key == 40 || key == 38) {
        //down or up pressed
        //get the coords
        var coords = obj.value.split(',');
        var s = getSelectionStart(obj);//helper function
        var j = 0;
        for (var i = 0, le = coords.length; i < le; i++) {
            j += coords[i].length;
            if (j > s) {
                //this is the coord we want
                if (key == 40 && coords[i] > 0) {
                    coords[i]--;
                }
                if (key == 38) {
                    coords[i]++;
                }
                break;
            }
            //jump one more because of comma
            j++;
        }
        obj.value = coords.join(',');
        if (obj.value != myimgmap.areas[obj.parentNode.aid].lastInput) {
            myimgmap._recalculate(obj.parentNode.aid, obj.value);//contains repaint
        }
        //set cursor back to its original position
        setSelectionRange(obj, s);
        return true;
    }
}

/**
 *    Gets the position of the cursor in the input box.
 *    @author    Diego Perlini
 *    @url    http://javascript.nwbox.com/cursor_position/
 */
function getSelectionStart(obj) {
    if (obj.createTextRange) {
        var r = document.selection.createRange().duplicate();
        r.moveEnd('character', obj.value.length);
        if (r.text === '') {
            return obj.value.length;
        }
        return obj.value.lastIndexOf(r.text);
    }
    else {
        return obj.selectionStart;
    }
}

/**
 *    Sets the position of the cursor in the input box.
 *    @link    http://www.codingforums.com/archive/index.php/t-90176.html
 */
function setSelectionRange(obj, start, end) {
    if (typeof end == "undefined") {
        end = start;
    }
    if (obj.setSelectionRange) {
        obj.focus(); // to make behaviour consistent with IE
        obj.setSelectionRange(start, end);
    }
    else if (obj.createTextRange) {
        var range = obj.createTextRange();
        range.collapse(true);
        range.moveEnd('character', end);
        range.moveStart('character', start);
        range.select();
    }
}


/**
 *    Called when one of the properties change, and the recalculate function
 *    must be called.
 *    @date    2006.10.24. 22:42:02
 *    @author    Adam Maschek (adam.maschek(at)gmail.com)
 */
function gui_input_change(e) {
    if (myimgmap.viewmode === 1) {
        return;
    }//exit if preview mode
    if (myimgmap.is_drawing) {
        return;
    }//exit if drawing
    //console.log('blur');
    var obj = (myimgmap.isMSIE) ? window.event.srcElement : e.currentTarget;
    //console.log(obj);
    var id = obj.parentNode.aid;


    //console.log(this.areas[id]);
    if (obj.name == 'img_href') {
        myimgmap.areas[id].ahref = obj.value;
    }
    else if (obj.name == 'img_alt') {
        myimgmap.areas[id].aalt = obj.value;
    }
    else if (obj.name == 'img_title') {
        myimgmap.areas[id].atitle = obj.value;
    }
    else if (obj.name == 'img_target') {
        myimgmap.areas[id].atarget = obj.value;
    }
    else if (obj.name == 'img_shape') {

        if (myimgmap.areas[id].shape != obj.value && myimgmap.areas[id].shape != 'undefined') {
            //shape changed, adjust coords intelligently inside _normCoords
            var coords = '';
            if (props[id]) {
                coords = props[id].getElementsByTagName('input')[2].value;
            }
            else {
                coords = myimgmap.areas[id].lastInput || '';
            }
            coords = myimgmap._normCoords(coords, obj.value, 'from' + myimgmap.areas[id].shape);
            if (props[id]) {
                props[id].getElementsByTagName('input')[2].value = coords;
            }
            myimgmap.areas[id].shape = obj.value;
            myimgmap._recalculate(id, coords);
            myimgmap.areas[id].lastInput = coords;
        }
        else if (myimgmap.areas[id].shape == 'undefined') {
            myimgmap.nextShape = obj.value;
        }
    }
    if (myimgmap.areas[id] && myimgmap.areas[id].shape != 'undefined') {
        myimgmap._recalculate(id, props[id].getElementsByTagName('input')[2].value);
        myimgmap.fireEvent('onHtmlChanged', myimgmap.getMapHTML());//temp ## shouldnt be here
    }
}

/**
 *    Called from imgmap when a new area is added.
 */
function gui_addArea(id) {
    //var id = props.length;
    //id = 1;
    props[id] = document.createElement('DIV');
    document.getElementById('form_container').appendChild(props[id]);

    props[id].id = 'img_area_' + id;
    props[id].aid = id;
    props[id].className = 'img_area';
    //hook ROW event handlers
    myimgmap.addEvent(props[id], 'mouseover', gui_row_mouseover);
    myimgmap.addEvent(props[id], 'mouseout', gui_row_mouseout);
    myimgmap.addEvent(props[id], 'click', gui_row_click);
    var temp = '<input type="text"  name="img_id" class="img_id" value="' + id + '" readonly="1"/>';
    //temp+= '<input type="checkbox" name="img_active" class="img_active" id="img_active_'+id+'" value="'+id+'">';
    //could be checkbox in the future
    temp += ' <input type="radio" name="img_active" class="img_active" id="img_active_' + id + '" value="' + id + '">';
    // temp+= '<input type="hidden" name="img_shape" class="img_shape" value="rectangle">';
    temp += '<select name="img_shape" class="img_shape" id="image_shape' + id + '" style="display: none;">';
    temp += '<option value="rect">rectangle</option>';
    // if (document.getElementById('dd_output').value != 'css') {
    temp += '<option value="circle">circle</option>';
    temp += '<option value="poly">polygon</option>';
    temp += '<option value="bezier1">bezier</option>';
    // }
    temp += '</select>';
    temp += ' 范围: <input type="text" name="img_coords" class="img_coords" id="img_coords' + id + '" value="">';
    // temp+= 'Href: <input type="text" name="img_href" class="img_href" value="">';
    // temp+= 'Alt: <input type="text" name="img_alt" class="img_alt" value="">';
    // temp+= ' 类型: <select name="img_target" class="img_target" id="img_target'+id+'">';
    // temp+= '<option value=""  >&lt;选择类型&gt;</option>';
    // temp+= '<option value="1"  >文字+音乐</option>';
    // temp+= '<option value="2" >图片+音乐</option>';
    // temp+= '<option value="3"   >视频</option>';
    // temp+= '<option value="4"   >地图应用</option>';
    // temp+= '</select>';
    temp += ' <img src="/client/imgmap/delete.gif" onclick="if (confirm(\'确定删除 ' + id + ' 号区域？\')){removeArea(' + id + ');}" alt="Delete selected area" style="cursor: pointer;margin-left:60px;" />';
    props[id].innerHTML = temp;
    //hook more event handlers to individual inputs
    // console.log(props[id].getElementsByTagName('input'));

    // myimgmap.addEvent(props[id].getElementsByTagName('input')[1],  'change', gui_cb_keydown);
    // myimgmap.addEvent(props[id].getElementsByTagName('input')[2],  'keydown', gui_coords_keydown);
    myimgmap.addEvent(props[id].getElementsByTagName('input')[2], 'change', gui_input_change);
    // myimgmap.addEvent(props[id].getElementsByTagName('input')[3],  'change', gui_input_change);
    // myimgmap.addEvent(props[id].getElementsByTagName('input')[4],  'change', gui_input_change);
    // myimgmap.addEvent(props[id].getElementsByTagName('select')[0], 'change', gui_input_change);
    // myimgmap.addEvent(props[id].getElementsByTagName('select')[1], 'change', gui_change_type);
    // if (myimgmap.isSafari) {
    // 	//need these for safari
    // 	myimgmap.addEvent(props[id].getElementsByTagName('select')[0], 'change', gui_row_click);
    // 	myimgmap.addEvent(props[id].getElementsByTagName('select')[1], 'change', gui_row_click);
    // }
    //set shape as nextshape if set
    // if (myimgmap.nextShape) {props[id].getElementsByTagName('select')[0].value = myimgmap.nextShape;}
    //alert(this.props[id].parentNode.innerHTML);
    // gui_row_select(id, true);
}

function gui_change_type() {
    var _id = $('input[name=img_active]:checked').val();
    $('.img_target').each(function (idx) {
        if (idx != _id) {
            this.value = '';
        }
    });
    $('.areaTypeInput').hide();
    if (this.value === '') {
        $('#areaTypeInput').hide();
        return;
    }
    $('#areaTypeInput').show();
    $('#areaTypeInput' + this.value).show();
    $('#clickAreaNumber').html(_id + ' 号区域');
    $('#clickAreaInformationTitle').html(this[this.value].text);
}

/**
 *    Called from imgmap when an area was removed.
 */
function gui_removeArea(id) {
    if (props[id]) {
        //shall we leave the last one?
        var pprops = props[id].parentNode;
        pprops.removeChild(props[id]);
        var lastid = pprops.lastChild.aid;
        props[id] = null;
        try {
            gui_row_select(lastid, true);
            myimgmap.currentid = lastid;
        }
        catch (err) {
            //alert('noparent');
        }
    }
}

/**
 *    Called from imgmap when mode changed to a given value (preview or normal)
 */
function gui_modeChanged(mode) {
    var nodes, i;
    if (mode == 1) {
        //preview mode
        if (document.getElementById('html_container')) {
            document.getElementById('html_container').disabled = true;
        }
        //disable form elements (inputs and selects)
        nodes = document.getElementById('form_container').getElementsByTagName("input");
        for (i = 0; i < nodes.length; i++) {
            nodes[i].disabled = true;
        }
        nodes = document.getElementById('form_container').getElementsByTagName("select");
        for (i = 0; i < nodes.length; i++) {
            nodes[i].disabled = true;
        }
        document.getElementById('i_preview').src = imgroot + 'edit.gif';
        document.getElementById('dd_zoom').disabled = true;
        // document.getElementById('dd_output').disabled = true;
    }
    else {
        //normal mode
        if (document.getElementById('html_container')) {
            document.getElementById('html_container').disabled = false;
        }
        //enable form elements (inputs and selects)
        nodes = document.getElementById('form_container').getElementsByTagName("input");
        for (i = 0; i < nodes.length; i++) {
            nodes[i].disabled = false;
        }
        nodes = document.getElementById('form_container').getElementsByTagName("select");
        for (i = 0; i < nodes.length; i++) {
            nodes[i].disabled = false;
        }
        document.getElementById('i_preview').src = imgroot + 'zoom.gif';
        document.getElementById('dd_zoom').disabled = false;
        // document.getElementById('dd_output').disabled = false;
    }
}

/**
 *    Called from imgmap with the new html code when changed.
 */
function gui_htmlChanged(str) {
    // var out = document.getElementById('dd_output').value;
    if (document.getElementById('html_container')) {
        // if (out == 'css') {
        // 	document.getElementById('html_container').value = output_css();
        // }
        // else if (out == 'wiki') {
        // 	document.getElementById('html_container').value = output_wiki();
        // }
        // else {
        document.getElementById('html_container').value = str;
        // }
    }
}

/**
 *    Called from imgmap with new status string.
 */
function gui_statusMessage(str) {
    if (document.getElementById('status_container')) {
        document.getElementById('status_container').innerHTML = str;
    }
    window.defaultStatus = str;//for IE
}

function gui_areaChanged(area) {
    var id = area.aid;
    if (props[id]) {
        if (area.shape) {
            props[id].getElementsByTagName('select')[0].value = area.shape;
        }
        if (area.lastInput) {
            props[id].getElementsByTagName('input')[2].value = area.lastInput;
        }
        if (area.ahref) {
            props[id].getElementsByTagName('input')[3].value = area.ahref;
        }
        if (area.aalt) {
            props[id].getElementsByTagName('input')[4].value = area.aalt;
        }
        if (area.atarget) {
            props[id].getElementsByTagName('select')[1].value = area.atarget;
        }
    }
}


/**
 *    Called when the grand HTML code loses focus, and the changes must be reflected.
 *    @date    2006.10.24. 22:51:20
 *    @author    Adam Maschek (adam.maschek(at)gmail.com)
 */
function gui_htmlBlur() {
    var elem = document.getElementById('html_container');
    var oldvalue = elem.getAttribute('oldvalue');
    if (oldvalue != elem.value && outputmode == 'imagemap') {
        //dirty
        myimgmap.setMapHTML(elem.value);
    }
}


/**
 *    Called when the optional html container gets focus.
 *    We need to memorize its old value in order to be able to
 *    detect changes in the code that needs to be reflected.
 *    @date    20-02-2007 17:51:16
 *    @author Adam Maschek (adam.maschek(at)gmail.com)
 */
function gui_htmlFocus() {
    var elem = document.getElementById('html_container');
    elem.setAttribute('oldvalue', elem.value);
    elem.select();
}

function gui_htmlShow() {
    toggleFieldset(document.getElementById('fieldset_html'), 1);
    document.getElementById('html_container').focus();
}

/**
 *    Change the labeling mode directly in imgmap config then repaint all areas.
 */
function changelabeling(obj) {
    myimgmap.config.label = obj.value;
    myimgmap._repaintAll();
}

/**
 *    Change the bounding box mode straight in imgmap config then relax all areas.
 *    (Relax just repaints the borders and opacity.)
 */
function toggleBoundingBox(obj) {
    obj.checked = !obj.checked;
    obj.innerHTML = '&nbsp; bounding box';
    if (obj.checked) {
        obj.innerHTML = '&raquo; bounding box';
    }
    myimgmap.config.bounding_box = obj.checked;
    myimgmap.relaxAllAreas();
    gui_toggleMore();
}

/**
 *    Toggles fieldset visibility by changing the className.
 *    External css needed with the appropriate classnames.
 *    @date    2006.10.24. 22:13:34
 *    @author    Adam Maschek (maschek@freemail.hu)
 */
function toggleFieldset(fieldset, on) {
    if (fieldset) {
        if (fieldset.className == 'fieldset_off' || on == 1) {
            fieldset.className = '';
        }
        else {
            fieldset.className = 'fieldset_off';
        }
    }
}

function gui_selectArea(obj) {
    if (lastClickId === obj.aid) {
        return;
    }
    lastClickId = obj.aid;
    var areaId = $("#img_area_"+obj.aid).attr('data-id');
    console.dir(obj);
    gui_row_select(obj.aid, true, false);
    $('#areaTypeInput').show();
    // $('#areaTypeInput'+this.value).show();
    $('#clickAreaNumber').html(obj.aid + ' 号区域');
    if (areaId>=0) {
        setAreaValue(areaId);
    } else {
        $('#areaTypeSelect').val('').multiselect('rebuild');
        $('#musicFilePreview1,#musicFilePreview2,#screenImagePreview2,#videoFilePreview3').html('');
        $('#musicFile1,#content1').val('');
        $('.areaTypeInput').hide();
    }
}

function gui_zoom() {
    var scale = document.getElementById('dd_zoom').value;
    var pic = document.getElementById('pic_container').getElementsByTagName('img')[0];
    if (typeof pic == 'undefined') {
        return false;
    }
    if (typeof pic.oldwidth == 'undefined' || !pic.oldwidth) {
        pic.oldwidth = pic.width;
    }
    if (typeof pic.oldheight == 'undefined' || !pic.oldheight) {
        pic.oldheight = pic.height;
    }
    pic.width = pic.oldwidth * scale;
    pic.height = pic.oldheight * scale;
    myimgmap.scaleAllAreas(scale);
}

function gui_loadImage(src, width, height) {
    //reset zoom dropdown
    document.getElementById('dd_zoom').value = '1';
    var pic = document.getElementById('pic_container').getElementsByTagName('img')[0];
    if (typeof pic != 'undefined') {
        //delete already existing pic
        pic.parentNode.removeChild(pic);
        delete myimgmap.pic;
    }

    myimgmap.loadImage(src, width, height);
}

function gui_outputChanged() {
    return true;
}


/**
 *    Tries to copy imagemap output or text parameter to the clipboard.
 *    If in special environment (eg AIR), use specific functions.
 *    @date    2006.10.24. 22:14:12
 *    @param    text    Text to copy, otherwise html_container will be used.
 */
function gui_toClipBoard(text) {
    if (typeof text == 'undefined') {
        text = document.getElementById('html_container').value;
    }
    try {
        if (window.clipboardData) {
            // IE send-to-clipboard method.
            window.clipboardData.setData('Text', text);
        }
        else if (typeof air == 'object') {
            air.Clipboard.generalClipboard.clear();
            air.Clipboard.generalClipboard.setData("air:text", text, false);
        }
    }
    catch (err) {
        myimgmap.log("Unable to set clipboard data", 1);
    }
}


/** INIT SECTION **************************************************************/

//instantiate the imgmap component, setting up some basic config values
myimgmap = new imgmap({
    mode: "editor2",
    custom_callbacks: {
        'onStatusMessage': function (str) {
            gui_statusMessage(str);
        },//to display status messages on gui
        'onHtmlChanged': function (str) {
            gui_htmlChanged(str);
        },//to display updated html on gui
        'onModeChanged': function (mode) {
            gui_modeChanged(mode);
        },//to switch normal and preview modes on gui
        'onAddArea': function (id) {
            gui_addArea(id);
        },//to add new form element on gui
        'onRemoveArea': function (id) {
            gui_removeArea(id);
        },//to remove form elements from gui
        'onAreaChanged': function (obj) {
            gui_areaChanged(obj);
        },
        'onSelectArea': function (obj) {
            gui_selectArea(obj);
        }//to select form element when an area is clicked
    },
    pic_container: document.getElementById('pic_container'),
    bounding_box: false
});

//array of form elements
props = [];
imgroot = 'example1_files/';
outputmode = 'imgmap';
gui_outputChanged();


// $('#color1').colorPicker();


/** OUTPUT FUNCTIONS **********************************************************/

/**
 *    Just grab the areas array and do whatever you wish.
 */
function output_css() {
    var html, coords, top, left, width, height;
    html = '<div class="imgmap_css_container" id="' + myimgmap.getMapId() + '">';

    //foreach areas
    for (var i = 0; i < myimgmap.areas.length; i++) {
        if (myimgmap.areas[i]) {
            if (myimgmap.areas[i].shape && myimgmap.areas[i].shape != 'undefined') {
                coords = myimgmap.areas[i].lastInput;
                top = myimgmap.areas[i].style.top;
                left = myimgmap.areas[i].style.left;
                width = myimgmap.areas[i].style.width;
                height = myimgmap.areas[i].style.height;
                html += '<a style="position: absolute; top: ' + top + '; left: ' + left + '; width: ' + width + '; height: ' + height + ';" ' +
                    ' alt="' + myimgmap.areas[i].aalt + '"' +
                    ' title="' + myimgmap.areas[i].aalt + '"' +
                    ' href="' + myimgmap.areas[i].ahref + '"' +
                    ' target="' + myimgmap.areas[i].atarget + '" ><em>' + myimgmap.areas[i].atitle + '</em></a>';
            }
        }
    }
    html += myimgmap.waterMark + '</div>';
    //alert(html);
    return html;

}

/**
 *    Grab the areas array and make wiki output.
 *    @link    http://www.mediawiki.org/wiki/Extension:ImageMap
 *    <imagemap>
 Image:Foo.jpg|200px|picture of a foo
 poly 131 45 213 41 210 110 127 109 [[Display]]
 poly 104 126 105 171 269 162 267 124 [[Keyboard]]
 rect 15 95 94 176   [[Foo type A]]
 # A comment, this line is ignored
 circle 57 57 20    [[Foo type B]]
 desc bottom-left
 </imagemap>

 */
function output_wiki() {
    var html, coords;
    html = '<imagemap>';
    if (typeof myimgmap.pic != 'undefined') {
        html += 'Image:' + myimgmap.pic.src + '|' + myimgmap.pic.title + '\n';
    }

    //foreach areas
    for (var i = 0; i < myimgmap.areas.length; i++) {
        if (myimgmap.areas[i]) {
            if (myimgmap.areas[i].shape && myimgmap.areas[i].shape != 'undefined') {
                coords = myimgmap.areas[i].lastInput.split(',').join(' ');
                html += myimgmap.areas[i].shape + ' ' + coords + ' [[' + myimgmap.areas[i].ahref + '|' + myimgmap.areas[i].aalt + ']]\n';
            }
        }
    }
    html += '#' + myimgmap.waterMark + '\n</imagemap>';
    //alert(html);
    return html;
}

/** BROWSERPLUS SECTION *******************************************************/

/*
myimgmap.loadScript("http://bp.yahooapis.com/2.1.6/browserplus-min.js");
window.setTimeout(bp_init, 2000);

function bp_init() {
	if (!myimgmap.loadedScripts["http://bp.yahooapis.com/2.1.6/browserplus-min.js"]) return;
	BrowserPlus.init(function(res) {
	  if (res.success) {  
	   BrowserPlus.require({  
	      services: [
		  	{service: 'DragAndDrop'},
		  	{service: "ImageAlter"}
			]},  
	      function(res) {
	        if (res.success) {
	          var dnd = BrowserPlus.DragAndDrop;
	          dnd.AddDropTarget(
	            {id: "pic_container"},
	            function(res) {
	            	document.getElementById("pic_container").innerHTML = '<em>Drag and drop an image here to start editing, or select source above.</em>';
	              dnd.AttachCallbacks({
	                id: "pic_container",
	                drop: bp_dropped
	              },
	              function(){});
	              
	          });
	        } else {
	          //alert("Error Loading Browserplus Services: " + res.error);
	        }
	      });
	  } else {
	    //alert("Failed to initialize BrowserPlus: " + res.error);
	  }
	});
}

function bp_dropped(arg) {  
	bp_renderImage(arg[0]);
}  


function bp_renderImage(img) {
	var params = {};
	params.file = img;
	BrowserPlus.ImageAlter.Alter(params,
		function (result) {
			if (result.success) {
				//remove em element first
				var ems = document.getElementById('pic_container').getElementsByTagName('em');
				if (ems[0]) {
					document.getElementById('pic_container').removeChild(ems[0]);
				}
				myimgmap.loadImage(result.value.url);
			}
	});
}
*/
