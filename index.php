<?php require_once "includes/functions.inc.php" ?>
<script language="javascript" type="text/javascript"
src="/javascript/jquery/jquery.js"></script>
<script src="./includes/d3/d3.min.js"></script>
<div id="vumeterdiv" style="display: none;">
<script type="text/javascript" src="<?php echo mtimePath('js/vumeterdiv.js')?>"></script>
<div id="vumetergraph" style="height:516px;overflow: hidden;"></div>
</div>
<?php
echo '<script type="text/javascript">',
     'vumeterShow();',
     '</script>'
;
?>