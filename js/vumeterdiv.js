// NEW VUMETER CODE  // WARNING: in developement
"use strict";
// var
var incrementLign     = 29; // incrementation between each ligns
var nbr_lign_per_col  = 16; // number of lign max per columns
// you can change the size here, the boudry and interior adapt automaticaly
var size_x  = 150; // size of the barlevel
var size_y  = 18; // the Y size of ling
var margBar = 4*size_y - 10; // the marge of the level

/* band drawing from channel area */
var dctop       = 5;
var dcbottom    = 5;
var dcleft      = 5;
var dcright     = 20;
var background_grad;

var dc_x                   = 0;
var tmp_dc_x               = 0;
var tmp_last_max_pos_x     = 0;
var tmp_last_max_pos_y     = 0;

// background
var background  = null;
var background_vertical_lines = null;

/* Speakers table */
var LBL_OLD               = "old";
var LBL_NEW               = "new";
var speakersLimiters      = [];
speakersLimiters[LBL_OLD] = 0;
speakersLimiters[LBL_NEW] = 0;

// list_column
var list_column = [null];

// idx
var idx = 0;

/* const labels */
var LBL_IN    = "in";
var LBL_OUT   = "out";
var LBL_AUX   = "aux";
var LBL_AUXIN = "aux_in";


// useful fonction
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
  	// Column Object, is the object of the column, it contain also the list of ligns for this column

  	// var
      column_Name         : "", // column_name is the name of the column, it's used to draw the column name and to verify what column it is
      labelsBox_CH        : [null], // labelsBox_CH is a rect object (box)
      labelsTxt_CH        : [null], // labelsTxt_CH is the number in the box
      last_max_pos_x      : [null], // last_max_pos_x is used to draw the background
      last_max_pos_y      : [null], // same
      dc_x                : [null], // same
      vol_background_area : [null], // vol_background_area is used to draw the gradient
      list_lign           : [], // list_lign is the list of ligns, logic
      nbr_lign            : 0, // nbr_lign is the number of ligns that must dispaly

      // function
      clear_var : function() {
      	// clear all var exept the column_Name and the list of ligns
        idx = 0;
      	this.labelsBox_CH        = [null];
  	    this.labelsTxt_CH        = [null];
  	    this.last_max_pos_x      = [null];
  	    this.last_max_pos_y      = [null];
  	    this.dc_x                = [null];
  	    this.vol_background_area = [null];
      },
      clear_listLign : function(){
        this.list_lign = [null];
      },
      verif_change : function(vumeter_stats, graph) {
      	// verif if there is change and if yes draw

        // foreach lign, verif
        var i = 0; // i is here to count the number of iteration
        this.list_lign.forEach(function(element){
          // forEach lign
          if(i < vumeter_stats.length){ // the if is here to verify that there is enough info in vumeter_stats
            element.verif_change(vumeter_stats[i][0], vumeter_stats[i][1], graph); // we call the verif_change func for each ligns with the info in vumeter_stats
            i++; // increment i to count the iteration
          }else{
            element.noInfo(graph);
          }
        });
        // limiter warning, the limiter is a seperate var in vumeter_stats and only apply on the outputs
        if (this.column_Name == "OUTPUTS 01 to 16" || this.column_Name == "OUTPUTS 17 to 32"){
          var i = 0; // same as up here, we count the number of iteration to make sure that we don't verify with empty info
          this.list_lign.forEach(function(element){
            // forEach ligns
            if(i < vumeter_stats.length){
              // and if there is an info
              element.limiterCheck(vumeter_stats[i][2], graph); // we use the limiterChecker function with the specific info of vumeter_stats
              i++; // increment i
            }
          });
        }
        this.clear_var(); // we clearVar to save ram but i'm not sure it's very useful
      },
      init : function(graph, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y){
      	// init the collum (/!\ init must only be call once when the vumeter appear /!\)

        // clear the vars, just in case
        this.clear_var();

        // forEach nbr ligns, create the lign object
        for (var i = 0; i < this.nbr_lign; i++) {
          // add a new lign in the list_lign lsit
          this.list_lign.push(novlign()); // novlign is a func that return a lign object, it's the equivalent of list_lign.add(new lign)
        }

        // call draw func
        this.draw(graph, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y);

      },
      draw : function(graph, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y){
      	// draw the base (/!\ draw must only be call once in init /!\)
        // the draw function is the function where everything is draw, or the other draw func is call

        // make the HDMI an AUX coll go down, it's to let the 7 other ligns of decoder be display
        if (this.column_Name == "AUX In" || this.column_Name == "HDMI / DOWNMIX"){
          // 260 is a litle at "vu de nez", maybe cool to count the number if lign of decoder and make down that much aux and hdmi
          sub_bar_y = sub_bar_y + 260;
        }
        if(this.column_Name == "AUX In"){
          // aux is on the same column as hdmi, so it must be dawn, same 150 is "vu de nez" so maybe cool to count the number of ligns in hdmi
          sub_bar_y = sub_bar_y + 150;
        }
        // draw the column name
        this.drawColumnName(graph, idx, sub_bar_x, sub_bar_y);
        // draw the background
        this.drawBack(graph, background_grad, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y);

        // draw all the ligns
        // set up var for the ligns
        var y_lign = sub_bar_y + incrementLign;
        var x_lign = sub_bar_x - 115; // the 115 is here to slide the interrior or the col to the left
        // the number of the lign
        var i = 0;
        if(this.column_Name == "DECODER 17 to 24" || this.column_Name == "OUTPUTS 17 to 32"){
          i = 17; // start at 17
        }else {
          i = 1; // start at 1
        }
        // the forEach that create the ligns
        this.list_lign.forEach(function(element){
          // draw the square
          element.init(graph, x_lign, y_lign, i); // init the ligns
          i      = i + 1; // increment i to verify if it doesn't go more that 16
          y_lign = y_lign + incrementLign; // go down for each ligns
        });

        // null var
        y_lign = null;
        x_lign = null;
        i      = null;
      },
      drawColumnName : function(graph, idx, sub_bar_x, sub_bar_y){
          // draw the column name

          // get the name
          var column_name = this.column_Name; // i don't think it's very nessessery but whatever
          // draw on the graph
          var labelCol    = graph.text((column_name).toString()).move((sub_bar_x), sub_bar_y);

          // it was i nthe original code, i don't know if it's still relevent but i let it to avoid error
          if(labelCol != null) {
              labelCol.addClass('vumeter-Column_Name');
          }else {
              //avoid  error in case of (column_name == "AUX In" && inputs_aux.AUXIn_isEnable == false)
          }

          // null var
          column_name = null;
          labelCol    = null;
      },
      drawBack : function(graph, background_grad, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y){
          // draw the background and line
          // all of this come from the original code and it's work so i don't touch it

          // var
          var idxArray = idx-1;
          dc_x                         = sub_bar_x - this.last_max_pos_x[idxArray] - dcleft - dcright - 2;
          tmp_last_max_pos_x           = this.last_max_pos_x[idxArray]
          this.dc_x[idxArray]          = dc_x;
          var dc_y = sub_bar_y - dctop -dcbottom;

          // draw background
          var vol_background_area = graph.rect(dc_x, dc_y).fill(background_grad).move(sub_pos_offset_x+dcleft+tmp_last_max_pos_x, sub_pos_offset_y+dctop);
          vol_background_area.addClass('vumeter-vol_background_area');
          this.vol_background_area[idxArray]   = vol_background_area;

          // null vars
          idxArray            = null;
          dc_y                = null;
          vol_background_area = null;
      },
      update : function(vumeter_stats, graph){
      	// update the column
        // just call verif_change
        this.verif_change(vumeter_stats, graph);
      }
  }

  // this just return the column object
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
    ], // CH_CONTENT_MAP is the list of name of lign
    // the green box
    labelsBox_CH       : [null],
    // the number
    number             : [null],
    // the level
    level              : [null],
    // level box background
    levelBox           : [null],
    levelBoxBoudry     : [null],
    // prev state
    prev_vumeter_stats : [null],
    // the position of the lign
    x                  : null,
    y                  : null,
    // the text that display the level in db
    dbNum              : [null],
    // the size of the lign bar
    size_x             : null,
    size_y             : null,
    // the libel box and txt
    lbBox              : [null],
    lbTxt              : [null],
    background_grad    : [null],
    levelBoxBoudryX    : [null],
    border             : [null],
    size_numero        : [null],

    // function
    init : function(graph, x_lign, y_lign, i){
      // init the lign
      this.clearVar(); // clear the var to avoid error
      this.draw(graph, x_lign, y_lign, i); // draw the static stuff
    },
    clearVar : function(){
      // clear the var like the name say

      this.labelsBox_CH       = [null];
      // the number
      this.number             = [null];
      // the level
      this.level              = [null];
      this.levelex            = [null];
      // level box background
      this.levelBox           = [null];
      this.levelBoxBoudry     = [null];
      // prev state
      this.prev_vumeter_stats = [null];
      this.x                  = null;
      this.y                  = null;
      this.size_x             = null;
      this.size_y             = null;
      this.lbBox              = [null];
      this.lbTxt              = [null];
    },
    draw : function(graph, x, y, i){
        // draw static stuff

        // set up the vars
        // remember the loc
        this.x = x;
        this.y = y;
        // remember the size of the Ligns
        this.size_x = size_x;
        this.size_y = size_y;
        // pos box boundry
        this.levelBoxBoudryX = x + margBar;
        // border
        this.border = 4;
        // size of the num (not the actual size of the num but including the marge)
        this.size_numero = 20;

        // box and number
        this.draw_labelBoxAndNumber(graph, x, y, i);
        // level box background
        this.draw_levelBox(graph, x, y);
    },
    draw_labelBoxAndNumber : function(graph, x, y, i){
      // draw the level box and number
      // draw the box
      this.labelsBox_CH = graph.rect(this.size_y, this.size_y).move(x, y).addClass('vumeter-labelsBox_LimiterOff_CH');
      // draw the number
      this.number = graph.text((i).toString()).move(this.x, this.y + (2*(this.size_y / 4))).attr("font-size", "15").attr({fill: '#f7f7f7'});
    },
    draw_levelBox : function(graph, x, y){
      // draw a level box

      // draw the boundry
      this.levelBoxBoudry = graph.rect(this.size_x, this.size_y).move(this.levelBoxBoudryX + this.size_numero, this.y).attr({fill: "#fcf9f9"}); // the boundry are currently white
      // draw the inside of the level box, with the gradient background
      this.levelBox = graph.rect(this.size_x - (this.border), this.size_y - (this.border)).move(this.levelBoxBoudryX + this.size_numero + (this.border/2), this.y + (this.border/2)).fill(background_grad);
      // draw a rect for the libel (next to the number)
      this.lbBox = graph.rect((3*this.size_y), this.size_y).move(this.x + this.size_numero, this.y).addClass('vumeter-labelsBox_LimiterOff_CH');
      // draw empty label text (contain the )
      this.lbTxt = graph.text("").move(this.x + this.size_numero, this.y + this.size_y - this.border).attr("font-size", "13").addClass('vumeter-labelsBoxTxt_CH');
      //draw level empty (its not a level but a reverse level, the background is the level and we draw a rect that hide the deff between the lign size and the actual level)
      this.level = graph.rect().attr({fill: '#262626'}); // the color back, actually grey
      this.dbNum = graph.text("").move(this.x + this.size_x/2 + margBar, this.y + (3*(this.size_y / 4))).attr("font-size", "13").addClass('vumeter-labelsBoxTxt_CH');/*vumeter-labelsBoxTxt_CH*/
    },
    update : function(vumeter_stats, graph){
        // update by calling verif_change
        this.verif_change(vumeter_stats, graph); // verif if there is change and redraw if there is
    },
    verif_change : function(stats, id, graph){
      // verif if there is change and redraw if there is

      // set the id
      var identifiant = id;
      // set up the db
      var db = dbLevel(stats);
      // barLevel change in function of the db and the size of each lign, i think
      var barLevel = this.dbBar(db);
      // verif change
      if(this.prev_vumeter_stats == null){
        // prev_vumeter_stats is normally never null, but it's to avoid error, we never know
      }else if (this.prev_vumeter_stats == db) {
        // do nothing because nothing change, var... var never change
      }else if (this.prev_vumeter_stats !== db) {
        // if different, redraw stuff
        // this is the bar level
        this.level.size((this.size_x - barLevel - (this.border)), size_y - (this.border));
        this.level.move((this.x + this.size_numero + margBar + (this.border/2) + this.size_x) - (this.size_x - barLevel), this.y + (this.border/2));
        // this is the db number
        this.dbNum.node.textContent = (precise_round(db, 1).toString());
        this.lbTxt.node.textContent = ((this.CH_CONTENT_MAP[identifiant]).toString());
        if (identifiant == 0 /* if it's off */){
          // set the label box to red to indicate that it's off
          this.lbBox.removeClass('vumeter-labelsBox_LimiterOff_CH');
          this.lbBox.addClass('vumeter-labelsContentTxtOff_CH');
        }else {
          // redraw at the original color
          this.lbBox.removeClass('vumeter-labelsContentTxtOff_CH');
          this.lbBox.addClass('vumeter-labelsBox_LimiterOff_CH');
        }
      }
      // replace the prev_vumeter_stats with the new one
      this.prev_vumeter_stats = db;

      // null some vars
      identifiant   = null;
      db            = null;
      barLevel      = null;
    },
    dbBar : function(db){
      // make the bar length in function of the db
      return ((100 + db)*(this.size_x-(this.border*2))/100); // the (100 - db) is to make it positiv, the (this.size_x-4)/100 is to rescale it to the bar level (no work to do)
    },
    limiterCheck : function(stats, graph){
      // limiter check, if it's not 0 (this mean that db > 0) make the square red
      if(stats != 0){
        this.labelsBox_CH.removeClass('vumeter-labelsBox_LimiterOff_CH');
        this.labelsBox_CH.addClass('vumeter-labelsContentTxtOff_CH');
      }else { // don't forget to remake it green, else it will be red forever
        this.labelsBox_CH.removeClass('vumeter-labelsContentTxtOff_CH');
        this.labelsBox_CH.addClass('vumeter-labelsBox_LimiterOff_CH');
      }
    },
    noInfo : function(graph){
      // if there is no info in the json (to many ligns)
      this.level.size((this.size_x - this.dbBar(-100) - (this.border)), size_y - (this.border));
      this.level.move((this.x + this.size_numero + margBar + (this.border/2) + this.size_x) - (this.size_x - this.dbBar(-100)), this.y + (this.border/2));
      // this is the db number
      this.dbNum.node.textContent = ("no Info in the JSON");
      this.lbTxt.node.textContent = ("no Info in the JSON");
    }
  }
  // return the lign object
  return lign;
}


function init(graph){
	// init the vumeter

	// var

  // here are the columns :
  // create the column object
  var decoder1_16  = novColumn();
  var decoder16_24 = novColumn();
  var outputs1_16  = novColumn();
  var outputs17_32 = novColumn();
  var HDMI_DOWNMIX = novColumn();
  var AUX_In       = novColumn();
	list_column  = [
		// declare all columns
		decoder1_16,
    decoder16_24,
		outputs1_16,
		outputs17_32,
		HDMI_DOWNMIX,
    AUX_In
	];
	// set up the column name
	list_column[0].column_Name = "DECODER 01 to 16";
  list_column[1].column_Name = "DECODER 17 to 24";
	list_column[4].column_Name = "OUTPUTS 01 to 16";
	list_column[5].column_Name = "OUTPUTS 17 to 32";
	list_column[2].column_Name = "HDMI / DOWNMIX";
  list_column[3].column_Name = "AUX In";
  // number of ligns for each column
  list_column[0].nbr_lign = 16;
  list_column[1].nbr_lign = 8;
  list_column[4].nbr_lign = 16;
  list_column[5].nbr_lign = 16;
  list_column[2].nbr_lign = 4;
  list_column[3].nbr_lign = 2;

	/* total graph area */
  var g_x     = graph.width();
  var g_y     = graph.height();

  background     = graph.rect(g_x, g_y);

  // draw middle lines
  graph.rect(1, g_y).stroke({ color: 'white', opacity: 1, width: 1 }).move(g_x/2, 0);
  graph.rect(1, g_y).stroke({ color: 'white', opacity: 1, width: 1 }).move((g_x/4)+2, 0);
  graph.rect(1, g_y).stroke({ color: 'white', opacity: 1, width: 1 }).move((g_x/4+g_x/2)-2, 0);

  // draw all column
  // sub bar y and x
  var sub_bar_y = 10;
  var sub_bar_x = g_x/8;
  // sub_pos_offset_y
  var sub_pos_offset_y = 500;
  var sub_pos_offset_x = 50;

  // this draw the column
  var i=0; // i is to count the number of iteration
  list_column.forEach(function(element){
      //init and draw
      if (element.column_Name == "AUX In" || element.column_Name == "HDMI / DOWNMIX" || i == 0 /* this made the Aux In collumn go under the HDMI one */){
        sub_bar_x = sub_bar_x;
      }else{
        sub_bar_x = sub_bar_x + 2*(g_x/8);
      }
      element.init(graph, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y); // init stuff
      i = i + 1; // increment i
  });

  // null var to save ram
  g_x               = null;
  g_y               = null;
  sub_bar_y         = null;
  sub_bar_x         = null;
  sub_pos_offset_y  = null;
  sub_pos_offset_x  = null;
}

function wsOpen() {
    // open the web socket
    var prefix = 'ws://';
    if(window.location.protocol === 'https:') {
        prefix = 'wss://';
    }
    var url              = prefix + window.location.host +'/ws/vumeter';
    vumeterdiv.websocket = new WebSocket(url);
    var websocket        = vumeterdiv.websocket;
    var graph            = vumeterdiv.graph;
    var reopen_timeout;

    websocket.onopen = function () {
        // close and re-open websocket after 2 min
        // to avoid stacking too many frames in buffer when computer is slow
        reopen_timeout = setTimeout(wsReopen, 60 * 60 * 1000);
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

    init(graph);

    // null vars
    prefix = 'ws://';
    url              = null;
    websocket        = null;
    graph            = null;
    reopen_timeout   = null;
}

function wsOnMessage(message, graph){
  var vumeter_stats = JSON.parse(message.data);
  updateCol(vumeter_stats, graph);
  vumeter_stats = [null];
}
function updateCol(vumeter_stats, graph){
  // update the each columns

  // DECODER
  var sliceto = 16
  list_column[0].update((vumeter_stats[LBL_IN].slice(0, sliceto)), graph);// 1 to 16
  list_column[1].update((vumeter_stats[LBL_IN].slice(sliceto, 24)), graph);// 16 to 24
  // OUTPUTS
  list_column[4].update((vumeter_stats[LBL_OUT].slice(0, sliceto)), graph);
  list_column[5].update((vumeter_stats[LBL_OUT].slice(sliceto, 32)), graph);
  // AUX
  list_column[2].update(vumeter_stats[LBL_AUX], graph);
  list_column[3].update(vumeter_stats[LBL_AUXIN], graph);

  sliceto = [null];
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

    // create graphs
    var graph = vumeterdiv.graph;
    graph.size($('#vumetergraph').width(), $('#vumetergraph').height());

    //WARNING: call only once this fonction because it's taking time and memory
    // draw background gradient
    background_grad = graph.gradient('linear', function(stop) {
        stop.at({ offset: 0.0, color: 'red',    opacity: 1 })
        stop.at({ offset: 0.1, color: 'yellow', opacity: 1 })
        stop.at({ offset: 0.2, color: 'green',  opacity: 1 })
        stop.at({ offset: 1.0, color: 'green',  opacity: 1 })
    });
    background_grad.from(1,0).to(0,0).attr({id: "background_grad"});

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

    // null graph
    graph = null;
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
function dbLevel(level) {
    /* compute level to dB level */
    var dbLevel;
    if(level <= 0) {
        dbLevel = -100.0;
    }else {
        var level = parseFloat(level);
        dbLevel   = 20 * (log10(level/65535));
        if(dbLevel > -0.1) {
            dbLevel = 0.0;
        }
    }
    return dbLevel;
}

function precise_round(num, dec){
  // function that round a number, it's just a copy paste from stack
  if ((typeof num !== 'number') || (typeof dec !== 'number')){
    return false;
  }
  var num_sign = num >= 0 ? 1 : -1;
  return (Math.round((num*Math.pow(10,dec))+(num_sign*0.0001))/Math.pow(10,dec)).toFixed(dec);
}
