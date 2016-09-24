<?php
namespace UserMeta;

global $userMeta;
// Expected: $formName

$formBuilder = new FormBuilder('form_editor', $formName);
?>

<div class="wrap">
	<div id="um_form_editor">
    
        <?php if( $formName && ! $formBuilder->isFound() ) : ?>
        <div class="alert alert-danger" role="alert">Form "<?php echo $formName; ?>" is not found. You can create a new form.</div>
        <?php endif; ?>

        <div class="panel panel-default">
			<div class="panel-body">
				<div class="form-inline" role="form">
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon">Form Name*</div>
							<input type="text" class="form-control" name="form_key"
								value="<?php echo $formName; ?>"
								placeholder="Enter unique form name">
						</div>
					</div>

					<div class="form-group">
						<ul class="nav nav-pills um_pills">
							<li class="nav active danger"><a href="#um_form_fields_tab"
								data-toggle="tab">Form Builder</a></li>
							<li class="nav"><a href="#um_form_settings_tab" data-toggle="tab">Settings</a></li>
						</ul>
					</div>

					<div class="form-group">
						<span class="um_error_msg"></span>
					</div>

					<div class="form-group pull-right">
						<button type="button" class="btn btn-primary um_save_button">Save
							Changes</button>
					</div>

				</div>
			</div>
		</div>


		<div class="tab-content">
			<div class="tab-pane fade in active" id="um_form_fields_tab">
				<div class="col-xs-12 col-sm-8">
					<div id="um_fields_container" class="metabox-holder">
                        <?php $formBuilder->displayFormFields();  ?>
                    </div>
				</div>

				<div id="um_steady_sidebar_holder" class="col-xs-12 col-sm-4 ">
					<div id="um_steady_sidebar">
						<div id="um_fields_selectors" class="panel-group">
                            <?php $formBuilder->sharedFieldsSelectorPanel(); ?>
                            <?php $formBuilder->fieldsSelectorPanels(); ?>
                        </div>

						<p class="">
							<button style="float: right" type="button"
								class="um_save_button btn btn-primary">Save Changes</button>
						</p>
						<p class="um_clear"></p>
						<p class="um_error_msg"></p>
					</div>
				</div>

			</div>

			<div class="tab-pane fade" id="um_form_settings_tab">
				<div class="panel panel-default">
					<div class="panel-body">
                    <?php echo $formBuilder->displaySettings(); ?>
                  </div>
				</div>
			</div>
		</div>

		<div id="um_additional_input" class="um_hidden">
            <?php echo $userMeta->methodName( 'formEditor', true ); ?>
            <?php echo $formBuilder->maxFieldInput(); ?>
            <?php echo $formBuilder->additional(); ?>            
        </div>

	</div>
</div>