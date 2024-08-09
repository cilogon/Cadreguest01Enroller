<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return false;

  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flows.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'edit';
  $args[] = $cadreguest01_enrollers[0]['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
  $this->Html->addCrumb($cadreguest01_enrollers[0]['CoEnrollmentFlowWedge']['CoEnrollmentFlow']['name'], $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flow_wedges';
  $args['action'] = 'index';
  $args['coef'] = $cadreguest01_enrollers[0]['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
  $args[] = $cadreguest01_enrollers[0]['CoEnrollmentFlowWedge']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flow_wedges.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flow_wedges';
  $args['action'] = 'edit';
  $args[] = $cadreguest01_enrollers[0]['CoEnrollmentFlowWedge']['id'];
  $this->Html->addCrumb($cadreguest01_enrollers[0]['CoEnrollmentFlowWedge']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_enrollment_flow_wedge_id', array('default' => $cadreguest01_enrollers[0]['CoEnrollmentFlowWedge']['id'])) . "\n";
?>

<ul id="<?php print $this->action; ?>_cadreguest01_enroller" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.cadreguest01_enroller.idp'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.cadreguest01_enroller.idp.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('idp'); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.cadreguest01_enroller.domains'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.cadreguest01_enroller.domains.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('domains'); ?>
    </div>
  </li>

  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
