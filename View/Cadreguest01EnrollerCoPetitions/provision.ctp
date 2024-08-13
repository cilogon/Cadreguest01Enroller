<?php

  $params = array();
  $params['title'] = _txt('pl.cadreguest01_enroller.provision.title', array($vv_email));

  print $this->element("pageTitleAndButtons", $params);
?>

<p>
<?php print _txt('pl.cadreguest01_enroller.provision.header', array($vv_email)) ?>
</p>

<?php
  print $this->Form->create(
    'CadreAuthenticator.Cadre',
    array(
      'inputDefaults' => array(
        'label' => false,
        'div' => false
      )
    )
  );

 print $this->Form->hidden('co_petition_id', array('default' => $co_petition_id));
 print $this->Form->hidden('co_enrollment_flow_wedge_id', array('default' => $vv_efwid));
 print $this->Form->hidden('cadre_authenticator_id', array('default' => $vv_authenticator['CadreAuthenticator']['id'])) . "\n";
 print $this->Form->hidden('co_person_id', array('default' => $vv_co_person_id)) . "\n";
?>

<div class="co-info-topbox">
  <i class="material-icons">info</i>
  <?php
    $maxlen = isset($vv_authenticator['CadreAuthenticator']['max_length'])
              ? $vv_authenticator['CadreAuthenticator']['max_length']
              : 64;
    $minlen = isset($vv_authenticator['CadreAuthenticator']['min_length'])
              ? $vv_authenticator['CadreAuthenticator']['min_length']
              : 8;
  
    print _txt('pl.cadreauthenticator.info', array($minlen, $maxlen));
  ?>
</div>
<ul id="<?php print $this->action; ?>_cadrepassword" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.cadreauthenticator.password.new'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('password'); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.cadreauthenticator.password.again'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('password2', array('type' => 'password')); ?>
    </div>
  </li>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit(_txt('op.submit')); ?>
      </div>
    </li>
</ul>

<?php
  print $this->Form->end();
