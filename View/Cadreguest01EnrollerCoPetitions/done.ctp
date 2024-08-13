<?php

  $params = array();
  $params['title'] = _txt('pl.cadreguest01_enroller.done.title');

  print $this->element("pageTitleAndButtons", $params);
?>

<p>
<?php print _txt('pl.cadreguest01_enroller.done.text', array($vv_return_url)); ?>
</p>
