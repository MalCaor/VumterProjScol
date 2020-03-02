# Vumeter - Doc :
*storm Audio*
*v0.1*

---

## Les Vars

- incrementLign 			| espace entre chque ligne
- nbr_lign_per_col 			| nombre de lignes maximum
- size_x  					| taille de level en x
- size_y  					| taille de level en y
- margBar 					| marge du level
- dctop   					| 
- dcbottom 					| 
- dcleft 					| 
- dcright 					|
- background_grad 			|
- dc_x  					|
- tmp_dc_x 					|
- tmp_last_max_pos_x 		|
- tmp_last_max_pos_y 		| 
- background 				| fond
- background_vertical_lines | lign vertical du fond
- LBL_OLD 					| libel old
- LBL_NEW 					| libel new
- speakersLimiters 			| limiter
- speakersLimiters[LBL_OLD] | limiter Old
- speakersLimiters[LBL_NEW] | limiter New
- list_column 				| liste des collones
- idx 						| starter index
- LBL_IN 					| label in
- LBL_OUT 					| lable out
- LBL_AUX  					| lable aux
- LBL_AUXIN 				| lable aux-in

---

## Fonction

**padStart**
*require* :
- none 
*used* :
- aucune idée (fonction du code précédant)
*input* :
- pad, 
- str, 
- padLeft
*output* :
- un string
*work* :
- si str = "undefined" : return pad
- si padLeft : return pad + str - taille de pad
- sinon : return str + pad à la taille de pad

**log10**
*require* :
- none
*used* :
- dbLevel
*input* :
- val,(int)
*output* :
- int
*work* :
- return the log of val

**get_txt_sizes**
*require* :
- none
*used* :
- aucune idée (fonction du code précédant)
*input* :
- graph, 
- txt, 
- column_name,
*output* :
- array
*work* :
- si column_name : ajoute la class vumeter-Column_Name à text
- set labelSize_x et labelSize_y qui sont la hauteur et longuer de text
- return l'array des deux

**novColumn** 
*require* :
- none
*used* :
- init() | pour set up les collones
*input* :
- nop
*output* :
- var column
*work* :
- return une var column

**novlign**
*require* :
- none
*used* :
- novColumn()
*input* :
- none
*output* :
- var lign
*work* :
- return une var lign

**init**
*require* :
- none
*used* :
- wsOpen()
*input* :
- graph | graph du vumeter
*output* :
- rien
*work* :
- cree les collones
- les met dans une liste
- les nommes
- leurs donnes leurs nombre de ligne
- set g_x et g_y qui sont les dimention du graph
- cree le backgroud
- cree 3 lignes pour découper en 4 le graph
- set the sub_bar and sub_pos
- foreach collones : draw it

**wsOpen**
*require* :
- none
*used* :
- wsReopen()
- vumeterShow()
*input* :
- none
*output* :
- nothing
*work* :
- set prefix du websocket (ws:// si http wss:// is https)
- set the link with the web socket
- si onopen : restet the timeout
- si onclose : si reopen_timeout != null : cleanTimeout et set reopen_timeout à null
- si onerror : alert et cache le vumeter
- si onmessage : wsOnMessage()
- init(graph)
- nullify les vars

**wsOnMessage**
*require* :
- none
*used* :
- wsOpen
*input* :
- message
- graph
*output* :
- nothing
*work* :
- set vumeter_stat avec le message JSON
- updateCol(vumeter_stats, graph)
- null vumeter_stats

**updateCol**
*require* :
- none
*used* :
- wsOnMessage
*input* :
- vumeter_stats | les infos pour le vumètre
- graph | le graph
*output* :
- nop
*work* :
- set sliceto le moment ou couper le message
- couper et répartir le message pour chaque collone

**wsReopen**
*require* :
- none
*used* :
- wsOpen
*input* :
- none
*output* :
- none
*work* :
- ferme le websocket
- wsOpen

**vumeterShow**
*require* :
- none
*used* :
- appeler par le php
*input* :
- none
*output* :
- none
*work* :
- show la div vumeterdiv
- si vumeterdiv n'a pas de graph : cree un svg nommée graph
- optenir les carac de graph
- crée le gradient
- fill le backgroud
- wsOpen()
- fait un lien ajax pour recevoir les donner

**vumeterHide**
*require* :
- none
*used* :
- wsOpen()
*input* :
- none
*output* :
- none
*work* :
- si vumeterdiv contient graph : supprime graph, supprime les vars...
- si le websocket est ouvert : le ferme
- cache la div vumeterdiv

**dbLevel**
*require* :
- none 
*used* :
- verif_change()
*input* :
- level | vient du JSON
*output* :
- dbLevel | volume en db
*work* :
- retourne le volume ne db

**precise_round**
*require* :
- none
*used* :
- verif_change()
*input* :
- num
- dec
*output* :
- int
*work* :
- si num ou dec ne sont pas des nombre : return false
- return num aroudie à la décimal dec

---

## Object 

**Column**
*vars* :
- column_Name | le nom de la colone
- labelsBox_CH | box svg du label
- labelsTxt_CH | text svg du label
- last_max_pos_x | utilisé pour déssiner le fond
- last_max_pos_y | utilisé pour déssiner le fond
- dc_x | utilisé pour déssiner le fond
- vol_background_area | utilisé pour déssiner le fond
- list_lign | liste de lignes
- nbr_lign | nombre de lignes
*fonction* :
- clear_var() | met la plupars des vars à null
- clear_listLign() | rend null la list_lign (séparé de clear var pour pouvoir la nullifier sans null les autres vars)
- verif_change(vumeter_stats, graph) | update l'état de chaque ligne
- init(graph, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y) | crée toutes les lignes puis draw
- draw(graph, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y) | draw les collones, puis chaque ligne
- drawColumnName(graph, idx, sub_bar_x, sub_bar_y) | draw le nom de la collone
- drawBack(graph, background_grad, idx, sub_bar_x, sub_bar_y, sub_pos_offset_x, sub_pos_offset_y) | draw le background
- update(vumeter_stats, graph) | appel verif_change

**Lign**
*vars* :
- CH_CONTENT_MAP | array de nom de ligne
- labelsBox_CH | boc label
- number | le nummero de ligne
- level | le level
- levelBox | le box du level
- levelBoxBoudry | la bordure du boxe du level
- prev_vumeter_stats | le level de la précédante update
- x | x de la ligne
- y | y de la ligne
- dbNum | le text de db
- size_x | la taille x de la ligne
- size_y | la taille y de la ligne
- lbBox | le label box
- lbTxt | le label text
- background_grad | le gradient (de vert à rouge)
- levelBoxBoudryX | la bordure du levelbox
- border | la bordure
- size_numero | la taille du numero
*fonction* :
- init() | initialize la ligne
- clearVar() | null la plupars des vars
- draw((graph, x, y, i) | draw la ligne
- draw_labelBoxAndNumber(graph, x, y, i) | draw un carré et le numéro de la ligne dedans
- draw_levelBox(graph, x, y) | draw le level
- verif_change(stats, id, graph) | vérifie si le db à changer, si oui update le level, sinon ne fait rien
- dbBar(db) | return la longueur du level en fonction de db
- noInfo(graph) | affiche "no Info in the JSON" si il n'y a pas d'info dans le json pour cette ligne