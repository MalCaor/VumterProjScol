// NEW VUMETER CODE  // WARNING: in developement

// var
var background  = null;
var dtop        = 25;
var dbottom     = 5;
var dleft       = 5;
var dright      = 5;
list_column     = null;
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
var background                = null;
var background_vertical_lines = null;

/* Speakers table */
var LBL_OLD               = "old";
var LBL_NEW               = "new";
var speakersLimiters      = [];
speakersLimiters[LBL_OLD] = 0;
speakersLimiters[LBL_NEW] = 0;

// idx
var idx = 0;

// list_column
var list_column = [];

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
      labelsTxt_CH_TXT    : [null], // labelsTxt_CH_TXT is never used TODO : remove
      last_max_pos_x      : [null], // last_max_pos_x is used to draw the background
      last_max_pos_y      : [null], // same
      dc_x                : [null], // same
      vol_background_area : [null], // vol_background_area is used to draw the gradient
      vol_mask_area       : [null], // never used TODO : remove
      labelsTxt_VOL       : [null], // never used TODO : remove
      list_lign           : [], // list_lign is the list of ligns, logic
      nbr_lign            : 0, // nbr_lign is the number of ligns that must dispaly

      // function
      clear_var : function() {
      	// clear all var exept the column_Name and the list of ligns
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
      },
      verif_change : function(vumeter_stats, graph) {
      	// verif if there is change and if yes draw

        // foreach lign, verif
        var i = 0; // i is here to count the number of iteration
        this.list_lign.forEach(function(element){
          // forEach lign
          if(i < vumeter_stats.length){ // the if is here to verify that there is enough info in vumeter_stats
            element.verif_change(vumeter_stats[i][0], graph); // we call the verif_change func for each ligns with the info in vumeter_stats
            i++; // increment i to count the iteration
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
        var y_lign = sub_bar_y;
        var x_lign = sub_bar_x-115;
        var i      = 1;
        // Why not just add 29 in the set up of y_lign?
        y_lign     = y_lign + 29;
        // the forEach that create the ligns
        this.list_lign.forEach(function(element){
          // draw the square
          if(i == 17){
            // if more than 16 go on the other collum
            y_lign = sub_bar_y + 27;
            x_lign = 2*graph.width()/8 + 10 ; // temporaire ou tout du moins je le crois
          }
          element.init(graph, x_lign, y_lign, i); // init the ligns
          i      = i + 1; // increment i to verify if it doesn't go more that 16
          y_lign = y_lign + 29; // make the next lign go down, 29 is how much space there is between ligns
        });
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

      },
      drawBack : function(graph, background_grad, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y){
          // draw the background and line
          // all of this come from the original code and it's work so i don't touch it

          // var
          var idxArray = idx-1;

          //calcul spec for each chan TODO : Why are they separate?
          if        ( this.column_Name == "DECODER" ) {
              dc_x                         = sub_bar_x - this.last_max_pos_x[idxArray] - dcleft - dcright - 2;
              tmp_last_max_pos_x           = this.last_max_pos_x[idxArray]
              this.dc_x[idxArray]          = dc_x;
          } else if ( this.column_Name == "LBL_OUT" ) {
              dc_x                         = sub_bar_x - this.last_max_pos_x[idxArray] - dcleft - dcright - 2;
              tmp_last_max_pos_x           = this.last_max_pos_x[idxArray]
              this.dc_x[idxArray]          = dc_x;
          } else if ( this.column_Name == "OUTPUTS 01 to 16" ) {
              dc_x                         = sub_bar_x - this.last_max_pos_x[idxArray] - dcleft - dcright - 2;
              tmp_last_max_pos_x           = this.last_max_pos_x[idxArray]
              this.dc_x[idxArray]          = dc_x;
          } else if ( this.column_Name == "OUTPUTS 17 to 32" ) {
              dc_x                         = sub_bar_x - this.last_max_pos_x[idxArray] - dcleft - dcright - 2;
              tmp_last_max_pos_x           = this.last_max_pos_x[idxArray]
              this.dc_x[idxArray]   = dc_x;
          } else if ( this.column_Name == "HDMI / DOWNMIX" ) {
              dc_x                         = sub_bar_x - this.last_max_pos_x[idxArray] - dcleft - dcright - 2;
              tmp_last_max_pos_x           = this.last_max_pos_x[idxArray]
              this.dc_x[idxArray]          = dc_x;
          }
          var dc_y = sub_bar_y - dctop -dcbottom;

          // draw gradient
          var vol_background_area = graph.rect(dc_x, dc_y).fill(background_grad).move(sub_pos_offset_x+dcleft+tmp_last_max_pos_x, sub_pos_offset_y+dctop);
          vol_background_area.addClass('vumeter-vol_background_area');
          if        ( this.column_Name == "DECODER" ) {
              this.vol_background_area[idxArray]   = vol_background_area;
          } else if ( this.column_Name == "LBL_OUT" ) {
              this.vol_background_area[idxArray]   = vol_background_area;
          } else if ( this.column_Name == "OUTPUTS 01 to 16" ) {
              this.vol_background_area[idxArray]   = vol_background_area;
          } else if ( this.column_Name == "OUTPUTS 17 to 32" ) {
              this.vol_background_area[idxArray]   = vol_background_area;
          } else if (this.column_Name  == "HDMI / DOWNMIX" ) {
              this.vol_background_area[idxArray]   = vol_background_area;
          }

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
    x                  : [null],
    y                  : [null],
    // the text that display the level in db
    dbNum              : [null],

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
      this.x                  = [null];
      this.y                  = [null];
    },
    draw : function(graph, x, y, i){
        // draw static stuff

        // box and number
        this.draw_labelBoxAndNumber(graph, x, y, i);
        // level box background
        this.draw_levelBox(graph, x, y);

        var idxArray = idx-1; // je sais pas ce que c'est mais j'ai peur de le suprimer, TODO : what is this?
    },
    draw_labelBoxAndNumber : function(graph, x, y, i){
      // draw the level box and number

      // chan square size
      var size = 17;
      // draw the box
      this.labelsBox_CH = graph.rect(size, size).move(x, y).attr({fill: '#0faa33'});
      // draw the number
      this.number = graph.text((i).toString()).move(x, y + 5 /* the +5 is to center the number */).attr("font-size", "15");
    },
    draw_levelBox : function(graph, x, y){
      // draw a level box

      // you can change the size here, the boudry and interior adapt automaticaly
      size_x = 200;
      size_y = 17;
      // draw the boundry
      this.levelBoxBoudry = graph.rect(size_x, size_y).move(x + 20, y).attr({fill: "#fcf9f9"});
      // draw the inside of the level box
      this.levelBox = graph.rect(size_x - 4, size_y - 4).move(x + 22, y + 2).attr({fill: "#484f4a"});
      //draw level empty
      this.level = graph.rect();
      this.dbNum = graph.text("").move(x + 100, y + 13).attr("font-size", "13");
      // remember the loc
      this.x = x;
      this.y = y;
    },
    drawlevel : function(graph){
      // draw the level
      // why is this empty?
    },
    update : function(vumeter_stats, graph){
        // update by calling verif_change
        this.verif_change(vumeter_stats, graph); // verif if there is change and redraw if there is
    },
    verif_change : function(stats, graph){
      // verif if there is change and redraw if there is

      // set up the db
      db = dbLevel(stats);
      // barLevel change in function of the db and the size of each lign, i think
      barLevel = this.dbBar(db);
      // arbitrary size y
      size_y = 17;
      // verif change
      if(this.prev_vumeter_stats == null){
        // prev_vumeter_stats is normally never null, but it's to avoid error, we never know
      }else if (this.prev_vumeter_stats == db) {
        // do nothing because nothing change, var... var never change
      }else if (this.prev_vumeter_stats !== db) {
        // if different, redraw stuff
        // this is the bar level
        this.level.size(barLevel, size_y - 4).move(this.x + 22, this.y + 2).attr({fill: "#f70202"});
        // this is the db number
        this.dbNum.node.textContent = (precise_round(db, 1).toString());
      }
      // replace the prev_vumeter_stats with the new one
      this.prev_vumeter_stats = db;
    },
    dbBar : function(db){
      // make the bar length in function of the db
      return ((100 + db)*197/100);
    },
    limiterCheck : function(stats, graph){
      // limiter check, if it's not 0 (this mean that db > 0) make the square red
      if(stats != 0){
        this.labelsBox_CH.attr({fill: "#f70202"});
      }else { // don't forget to remake it green, else it will be red forever
        this.labelsBox_CH.attr({fill: "#0faa33"});
      }
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
	list_column = [
		// declare all columns
		decoder      = novColumn(),
		outputs1_16  = novColumn(),
		outputs17_32 = novColumn(),
		HDMI_DOWNMIX = novColumn(),
    AUX_In       = novColumn()
	]
	// set up the column name
	list_column[0].column_Name = "DECODER";
	list_column[3].column_Name = "OUTPUTS 01 to 16";
	list_column[4].column_Name = "OUTPUTS 17 to 32";
	list_column[1].column_Name = "HDMI / DOWNMIX";
  list_column[2].column_Name = "AUX In";
  // number of ligns for each column
  list_column[0].nbr_lign = 24;
  list_column[3].nbr_lign = 16;
  list_column[4].nbr_lign = 16;
  list_column[1].nbr_lign = 4;
  list_column[2].nbr_lign = 2;

	/* total graph area */
  var g_x     = graph.width();
  var g_y     = graph.height();

  // draw black background
  var d_x        = g_x - dleft - dright;
  var d_y        = g_y - dtop - dbottom;
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
      if (element.column_Name !== "AUX In" && i !=0 /* this made the Aux In collumn go under the HDMI one */){
        sub_bar_x = sub_bar_x + 2*(g_x/8);
      }
      element.init(graph, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y); // init stuff
      i = i + 1; // increment i
  });
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

    websocket.onopen = function () {
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

    init(graph);
}

function wsOnMessage(message, graph){
  var vumeter_stats = JSON.parse(message.data);
  updateCol(vumeter_stats, graph);
}
function updateCol(vumeter_stats, graph){
  // update the each columns
  list_column[0].update(vumeter_stats[LBL_IN], graph);
  var sliceto = 16; // it's the slice between 1-16 to 17-32
  list_column[3].update((vumeter_stats[LBL_OUT].slice(0, sliceto)), graph);
  list_column[4].update((vumeter_stats[LBL_OUT].slice(sliceto, 32)), graph);
  list_column[1].update(vumeter_stats[LBL_AUX], graph);
  list_column[2].update(vumeter_stats[LBL_AUXIN], graph);
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
