<?php
  $params = array();
  $params['title'] = _txt('pl.access02_enroller.title');

  print $this->Form->create(
    false,
    array(
      'inputDefaults' => array(
        'label' => false,
        'div' => false
      )
    )
  );

 print $this->Form->hidden('co_petition_id', array('default' => $vv_coPetitionId));
 print $this->Form->hidden('co_enrollment_flow_wedge_id', array('default' => $vv_efwid));
 print $this->Form->hidden('token', array('default' => $vv_token));

?> 

<ul id="<?php print $this->action; ?>_cadreguest01_enroller" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.cadreguest01_enroller.email'); ?>
      <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.cadreguest01_enroller.email.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->text('email'); ?>
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

<?php print $this->Form->end(); ?>

</div>
