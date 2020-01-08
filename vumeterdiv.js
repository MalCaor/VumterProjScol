// NEW VUMETER CODE  // WARNING: in developement

// var
var background = null;
var dtop        = 25;
var dbottom     = 5;
var dleft       = 5;
var dright      = 5;
list_column = null;
/* band drawing from channel area */
var dctop       = 5;
var dcbottom    = 5;
var dcleft      = 5;
var dcright     = 20;
var background_grad;
/* size of char in pixel */
var char_pos_x_y                 = 0;
var char_pos_x_y_column_name     = 0;

var dc_x                   = 0;
var tmp_dc_x               = 0;
var tmp_last_max_pos_x     = 0;
var tmp_last_max_pos_y     = 0;

/* size AUX In column name offet */
var column_name_AUXIn_pos_y_offset = 150;

// background
var background = null;
var background_vertical_lines = null;

/* Speakers table */
var LBL_OLD = "old";
var LBL_NEW = "new";
var speakersLimiters = [];
speakersLimiters[LBL_OLD] = 0;
speakersLimiters[LBL_NEW] = 0;

// idx
var idx = 0;

// list_column
var list_column = [];


// usful fonction
//WARNING: String.prototype.padStart(x, y) is not working on Internet Explorer and Edge
function padStart(pad, str, padLeft) {
    if (typeof str === 'undefined') {
        return pad;
    }

    if (padLeft) {
        return (pad + str).slice(-pad.length);
    } else {
        return (str + pad).substring(0, pad.length);
    }
}

//WARNING: Math.log10(x) is not working on Internet Explorer
function log10(val) {
    return Math.log(val) / Math.LN10;
}

// get txtsize
function get_txt_sizes(graph, txt, column_name) {
    // get max text size
    var text = graph.text(txt);
    if (column_name == true) {
        text = text.addClass('vumeter-Column_Name');
    }
    var labelSize_x = text.bbox().width;
    var labelSize_y = text.bbox().height;
    text.remove();

    return {
        labelSize_x: labelSize_x,
        labelSize_y: labelSize_y
    };
}


//   COLUMN
function novColumn(){
  var column = {
  	// Column Object

  	// var
      column_Name         : "",
      labelsBox_CH        : [null],
      labelsTxt_CH        : [null],
      labelsTxt_CH_TXT    : [null],
      last_max_pos_x      : [null],
      last_max_pos_y      : [null],
      dc_x                : [null],
      vol_background_area : [null],
      vol_mask_area       : [null],
      labelsTxt_VOL       : [null],
      list_lign           : [null],

      // function
      clear_var : function() {
      	// clear all var exept the column_Name
        idx = 0;
      	this.labelsBox_CH        = [null];
  	    this.labelsTxt_CH        = [null];
  	    this.labelsTxt_CH_TXT    = [null];
  	    this.last_max_pos_x      = [null];
  	    this.last_max_pos_y      = [null];
  	    this.dc_x                = [null];
  	    this.vol_background_area = [null];
  	    this.vol_mask_area       = [null];
  	    this.labelsTxt_VOL       = [null];
        this. list_lign          = [null];
      },
      verif_change : function(message, graph) {
      	// verif if there is change and if yes draw

      	// TODO : make verif_change func

          // the JSON message from the ws
          var vumeter_stats = JSON.parse(message.data);

          // foreach lign, verif
          /* VERIF LIGNS
          list_lign.forEach(element => {
              // it verif all lign
              if(element.verif){
                  // if it's different, redraw
                  element.draw();
              }
          });*/
      },
      init : function(){
      	// init the collum (/!\ init must only be call once when the vumeter appear /!\)

      	// TODO : make init

          // clear the vars
          this.clear_var();

      	// call draw func
      	this.draw();
      },
      draw : function(graph, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y){
      	// draw the base (/!\ draw must only be call once in init /!\)

      	// TODO : make draw

          // draw the column name
          this.drawColumnName(graph, idx, sub_bar_x);
          // draw the background
          this.drawBack(graph, background_grad, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y);
      },
      drawColumnName : function(graph, idx, sub_bar_x){
          // draw the column name

          // TODO : make drawColumnName

          // get the name
          var column_name = this.column_Name;
          // draw on the graph
          var labelCol = null;
          if(column_name == "AUX In") {
              var labelCol = graph.text((column_name).toString()).move((sub_bar_x), ((char_pos_x_y_column_name.labelSize_y/2)+column_name_AUXIn_pos_y_offset));
          }else if(column_name != "AUX In") {
              var labelCol = graph.text((column_name).toString()).move((sub_bar_x), (char_pos_x_y_column_name.labelSize_y/2));
          }

          if(labelCol != null) {
              labelCol.addClass('vumeter-Column_Name');
          }else {
              //avoid  error in case of (column_name == "AUX In" && inputs_aux.AUXIn_isEnable == false)
          }

      },
      drawBack : function(graph, background_grad, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y){
          // draw the background and line

          // var
          var idxArray = idx-1;

          //calcul spec for each chan
          if        ( this.column_Name == "DECODER" ) {
              dc_x                         = sub_bar_x - this.last_max_pos_x[idxArray] - dcleft - dcright - 2;
              tmp_last_max_pos_x           = this.last_max_pos_x[idxArray]
              this.dc_x[idxArray]       = dc_x;
          } else if ( this.column_Name == "LBL_OUT" ) {
              dc_x                         = sub_bar_x - this.last_max_pos_x[idxArray] - dcleft - dcright - 2;
              tmp_last_max_pos_x           = this.last_max_pos_x[idxArray]
              this.dc_x[idxArray] = dc_x;
          } else if ( this.column_Name == "OUTPUTS 01 to 16" ) {
              dc_x                         = sub_bar_x - this.last_max_pos_x[idxArray] - dcleft - dcright - 2;
              tmp_last_max_pos_x           = this.last_max_pos_x[idxArray]
              this.dc_x[idxArray] = dc_x;
          } else if ( this.column_Name == "OUTPUTS 17 to 32" ) {
              dc_x                         = sub_bar_x - this.last_max_pos_x[idxArray] - dcleft - dcright - 2;
              tmp_last_max_pos_x           = this.last_max_pos_x[idxArray]
              this.dc_x[idxArray]   = dc_x;
          } else if ( this.column_Name == "HDMI / DOWNMIX" ) {
              dc_x                         = sub_bar_x - this.last_max_pos_x[idxArray] - dcleft - dcright - 2;
              tmp_last_max_pos_x           = this.last_max_pos_x[idxArray]
              this.dc_x[idxArray]    = dc_x;
          }
          var dc_y = sub_bar_y - dctop -dcbottom;

          // draw gradient
          var vol_background_area = graph.rect(dc_x, dc_y).fill(background_grad).move(sub_pos_offset_x+dcleft+tmp_last_max_pos_x, sub_pos_offset_y+dctop);
          vol_background_area.addClass('vumeter-vol_background_area');
          if        ( this.column_Name == "DECODER" ) {
              this.vol_background_area[idxArray]       = vol_background_area;
          } else if ( this.column_Name == "LBL_OUT" ) {
              this.vol_background_area[idxArray] = vol_background_area;
          } else if ( this.column_Name == "OUTPUTS 01 to 16" ) {
              this.vol_background_area[idxArray] = vol_background_area;
          } else if ( this.column_Name == "OUTPUTS 17 to 32" ) {
              this.vol_background_area[idxArray]   = vol_background_area;
          } else if (this.column_Name == "HDMI / DOWNMIX" ) {
              this.vol_background_area[idxArray]    = vol_background_area;
          }

          // draw black mask over background gradient
          /*var vol_mask_area = background_grad.bbox();
          vol_mask_area = graph.rect(barheight, dc_y).move(sub_pos_offset_x+tmp_last_max_pos_x+dleft+dc_x-barheight, sub_pos_offset_y+dctop);
          if        (this.column_Name == "DECODER") {
              decoder.vol_mask_area[idxArray]       = vol_mask_area;
          } else if (this.column_Name == "LBL_OUT") {
              outputs_01_16.vol_mask_area[idxArray] = vol_mask_area;
          } else if (this.column_Name == "OUTPUTS 01 to 16") {
              outputs_17_32.vol_mask_area[idxArray] = vol_mask_area;
          } else if (this.column_Name == "OUTPUTS 17 to 32") {
              outputs_aux.vol_mask_area[idxArray]   = vol_mask_area;
          } else if (this.column_Name == "HDMI / DOWNMIX") {
              inputs_aux.vol_mask_area[idxArray]    = vol_mask_area;
          }*/

      },
      update : function(message, graph){
      	// update the column

      	// TODO : make update

          // verif chang
          this.verif_change(message, graph);
      }
  }

  return column;
}


//         LIGN
function novlign(){
  var lign = {
      // lign object

      // var
      CH_CONTENT_MAP : ['OFF', 'LF', 'RF', 'CF', 'LS', 'RS', 'LB', 'RB', 'CB',
          'LFH', 'RFH', 'CFH', 'LSH', 'RSH', 'LBH', 'RBH',
          'LFT', 'RFT', 'LMT', 'RMT', 'LBT', 'RBT', 'TOP',
          'SUB', 'LW', 'RW', 'HI', 'VI', 'MS', 'LRS1', 'RRS1', 'CBH',
          'AUX_LF', 'AUX_RF', 'DML', 'DMR', 'Mono','DM_LT','DM_RT',
          'Left','Right','AUX_M', 'AUX_BI_L', 'AUX_BI_R', 'LSC','RSC','LS1','RS1'
      ],

      // function
      init : function(){
          // init
      },
      draw : function(){
          // draw

          // chan square

      },
      update : function(){
          // update
          this.verif_change();
      },
      verif_change : function(){
          // verif change
      }

  }
  return lign;
}


function init(graph){
	// init the vumeter

	// var
	list_column = [
		// declare all columns
		decoder      = novColumn(),
		outputs1_16  = novColumn(),
		outputs17_32 = novColumn(),
		HDMI_DOWNMIX = novColumn()
	]

	// set up the column
	list_column[0].column_Name      = "DECODER";
	list_column[1].column_Name  = "OUTPUTS 01 to 16";
	list_column[2].column_Name = "OUTPUTS 17 to 32";
	list_column[3].column_Name = "HDMI / DOWNMIX";


	/* total graph area */
    var g_x     = graph.width();
    var g_y     = graph.height();

    // draw black background
    var d_x     = g_x - dleft - dright;
    var d_y     = g_y - dtop - dbottom;
    background     = graph.rect(g_x, g_y);

    // draw middle lines
    graph.rect(1, g_y).stroke({ color: 'white', opacity: 1, width: 1 }).move(g_x/2, 0);
    graph.rect(1, g_y).stroke({ color: 'white', opacity: 1, width: 1 }).move((g_x/4)+2, 0);
    graph.rect(1, g_y).stroke({ color: 'white', opacity: 1, width: 1 }).move((g_x/4+g_x/2)-2, 0);

    // draw all column
    // sub bar y and x
    var sub_bar_y = d_y/9;
    var sub_bar_x = g_x/8;
    // sub_pos_offset_y
    var sub_pos_offset_y = 500;
    var sub_pos_offset_x = 50;
    list_column.forEach(function(element){
        //draw
        element.draw(graph, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y); // pas ça
        // sub_bar_x
        sub_bar_x = sub_bar_x + 2*(g_x/8);
        // sub_pos_offset_x
        //sub_pos_offset_x = sub_pos_offset_x + 200;
    });
}

function wsOpen() {
    // open the web socket
    var prefix = 'ws://';
    if(window.location.protocol === 'https:') {
        prefix = 'wss://';
    }
    var url = prefix + window.location.host +'/ws/vumeter';
    vumeterdiv.websocket = new WebSocket(url);
    var websocket = vumeterdiv.websocket;
    var graph = vumeterdiv.graph;
    init(graph); // tmp : pas ça!, en fait si
    websocket.onopen = function () {
        //console.log('websocket opened');
        // close and re-open websocket after 2 min
        // to avoid stacking too many frames in buffer when computer is slow
        reopen_timeout = setTimeout(wsReopen, 2 * 60 * 1000);
    };
    websocket.onclose = function () {
        if(reopen_timeout !== null) {
            clearTimeout(reopen_timeout);
            reopen_timeout = null;
        }
    };
    websocket.onerror = function () {
        alert('Unable to open vumeter connection');
        vumeterHide();
        return;
    };
    websocket.onmessage = function(message) {
        wsOnMessage(message, graph);
    };
}

function wsOnMessage(message, graph){
  list_column.forEach(function(element){
    // Update each column
    element.update(message, graph);
  });
}

function wsReopen() {
    vumeterdiv.websocket.close();
    wsOpen();
}

function vumeterShow() {

    $("#vumeterdiv").show();
    // instancy SVG graph
    if (!(vumeterdiv.hasOwnProperty('graph'))) {
        var vumeter_graph = SVG('vumetergraph');
        //Default size 1013x226
        vumeterdiv.graph = vumeter_graph.size(1024,512);
    }

    // Get Zone2Source is enable
    /* inputs_aux
    setInterval(function() {
        if($("#vumeterdiv").is(":visible")) {
            $.ajax({
                type: "POST",
                url: "scripts/ajaxMYSQL.php",
                dataType: "json",
                data: {
                    submit: 'getState',
                    key: 'Zone2Source'
                },
                success: function(datas) {
                    if(datas == 0) {
                        inputs_aux.AUXIn_isEnable = false;
                    }else {
                        inputs_aux.AUXIn_isEnable = true;
                    }
                }
            });
        }
    }, 1000);*/

    // create graphs
    var graph = vumeterdiv.graph;
    graph.size($('#vumetergraph').width(), $('#vumetergraph').height());

    //WARNING: call only once this fonction because it's taking time and memory
    // draw background gradient
    background_grad = graph.gradient('linear', function(stop) {
        stop.at({ offset: 0.0, color: 'red',     opacity: 1 })
        stop.at({ offset: 0.1, color: 'yellow', opacity: 1 })
        stop.at({ offset: 0.2, color: 'green',     opacity: 1 })
        stop.at({ offset: 1.0, color: 'green',     opacity: 1 })
    });
    background_grad.from(1,0).to(0,0);

    // get x and y position (for 1 character)
    char_pos_x_y                 = get_txt_sizes(graph, "9", false);
    char_pos_x_y_column_name     = get_txt_sizes(graph, "9", true);

    wsOpen();

    setInterval(function() {
        if($("#vumeterdiv").is(":visible")) {
            $.ajax({
                type: "POST",
                url: "scripts/ajaxMYSQL.php",
                dataType: "json",
                data: {
                    submit: 'getSpeakersLimiters'
                },
                success: function(datas) {
                    //Array copy by reference
                    speakersLimiters[LBL_NEW] = datas;

                    if(speakersLimiters[LBL_OLD] == 0) { //If first time
                        //Array structure copy
                        speakersLimiters[LBL_OLD] = datas.concat();

                        for (var i in speakersLimiters[LBL_OLD]) {
                            //Array value copy by new assignment
                            var obj = speakersLimiters[LBL_OLD][i];
                            speakersLimiters[LBL_OLD][i] = Object.assign({}, obj);

                            //Set to not initialised value to force a first display
                            speakersLimiters[LBL_OLD][i].limiterValue = "";
                            speakersLimiters[LBL_OLD][i].limiterEnable = "";
                        }
                    }
                }
            });
        }
    }, 1000);
}

function vumeterHide() {
    if (vumeterdiv.hasOwnProperty('graph')) {
        vumeterdiv.graph.clear();
        $('#vumetergraph').empty();
        delete vumeterdiv.graph;
        list_column.forEach(function(element){
          // Update each column
          element.clear_var();
        });
    }
    if (vumeterdiv.hasOwnProperty('websocket')) {
        vumeterdiv.websocket.close();
    }
    $('#vumeterdiv').hide();
}
