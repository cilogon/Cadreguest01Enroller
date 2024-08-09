<?php
  $params = array();
  $params['title'] = _txt('pl.access02_enroller.title');

  print $this->Form->create(
    'Access02Enroller.Access02Petition',
    array(
      'inputDefaults' => array(
        'label' => false,
        'div' => false
      )
    )
  );

 print $this->Form->hidden('co_petition_id', array('default' => $co_petition_id));
 print $this->Form->hidden('co_enrollment_flow_wedge_id', array('default' => $vv_efwid));
 print $this->Form->hidden('access_organization_id');

 // Unlock the field so we can manipulate with JavaScript and not trigger the security code.
 $this->Form->unlockField('Access02Petition.access_organization_id');
?> 
<script>
  $(function() {
    $("#cannot-find-organization-dialog").dialog({
      autoOpen: false,
      buttons: {
      "<?php print _txt('pl.access02_enroller.dialog.button.add'); ?>": function() {
        $(this).dialog("close");
        displaySpinner();
        $("#Access02PetitionAccessOrganizationId").val(<?php print $vv_access_organization_other_id ?>);
        $("#Access02PetitionPetitionerAttributesForm").submit();
      },
          "<?php print _txt('pl.access02_enroller.dialog.button.cancel'); ?>": function() { $(this).dialog("close"); },
        },
      modal: true,
      show: {
        effect: "fade"
        },
      hide:{
        effect: "fade"
        }
      }
    );

    $("#organization-choose").autocomplete({
      source: "<?php print $this->Html->url(array('plugin' => 'access_organization', 'controller' => 'access_organizations', 'action' => 'find', 'co' => $cur_co['Co']['id'])); ?>",
      minLength: 3,
      select: function (event, ui) {
        $("#organization-choose").hide();
        $("#organization-choose-name").text(ui.item.label).show();
        $("#Access02PetitionAccessOrganizationId").val(ui.item.value);
        $("#organization-choose-button").prop('disabled', false).focus();
        $("#organization-choose-clear-button").show();
        return false;
      },
      search: function (event, ui) {
        $("#organization-choose-search-container .co-loading-mini").show();
      },
      focus: function (event, ui) {
        event.preventDefault();
        $("#organization-choose-search-container .co-loading-mini").hide();
        $("#organization-choose").val(ui.item.label);
      },
      close: function (event, ui) {
        $("#organization-choose-search-container .co-loading-mini").hide();
      }
    });

    $("#organization-choose-button").click(function(e) {
      displaySpinner();
      e.preventDefault();
      $("#Access02PetitionPetitionerAttributesForm").submit();
    });

    $("#organization-choose-clear-button").click(function() {
      stopSpinner();
      $("#organization-choose-name").hide();
      $("#Access02PetitionAccessOrganizationId").val("");
      $("#organization-choose-button").prop('disabled', true).focus();
      $("#organization-choose-clear-button").hide();
      $("#organization-choose").val("").show().focus();
      return false;
    });

    $('[data-toggle="tooltip"]').tooltip();

    $("#cannot-find-organization-button").click(function(e) {
      e.preventDefault();
      $("#organization-choose-name").hide();
      $("#Access02PetitionAccessOrganizationId").val("");
      $("#organization-choose-button").prop('disabled', true).focus();
      $("#organization-choose-clear-button").hide();
      $("#organization-choose").val("").show().focus();
      $("#cannot-find-organization-dialog").dialog("open");
    });

  });

</script>

<h2>Your registration is not complete yet</h2>

<p>
You must select your primary home organization and verify
your email address before your registration is complete.
</p>

<p>
Type in the box below to find and select your primary home organization.
</p>

<div id="organization-choose-search-container">
  <label for="organization-choose" class="col-form-label-sm">Primary Home Organization: </label>
  <span class="co-loading-mini-input-container">
    <input id="organization-choose" type="text" class="form-control-sm" placeholder="enter organization name" />
    <span class="co-loading-mini"><span></span><span></span><span></span></span>
  </span>
  <span id="organization-choose-name" style="display: none;"></span>
  <button id="organization-choose-button" class="btn btn-primary btn-sm" disabled="disabled"><?php print _txt('Select'); ?></button>
  <button id="organization-choose-clear-button" class="btn btn-sm" style="display: none;"><?php print _txt('op.clear'); ?></button>
</div>

<div id="organization-choose-other">
  <button id="cannot-find-organization-button" class="btn btn-primary btn-sm"><?php print _txt('pl.access02_enroller.button.cannot.find'); ?></button>
</div>

<?php print $this->Form->end(); ?>

<div id="cannot-find-organization-dialog" title="<?php print _txt('pl.access02_enroller.dialog.title'); ?>" style="display:none">
<p>Can't find your organization? Click Add My Organization to request your organization be added to the system. Please note that adding a new
organization will delay your registration.
</p>
</div>
