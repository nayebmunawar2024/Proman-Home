
<?php echo ajax_modal_template_header(TEXT_HEADING_DELETE) ?>

<?php echo form_tag('delete', url_for('composite_unique_fields/rules','action=delete&id=' . $_GET['id'] . '&entities_id=' .$_GET['entities_id'])) ?>
    
<div class="modal-body">    
<?php echo TEXT_ARE_YOU_SURE ?>
</div> 
 
<?php echo ajax_modal_template_footer(TEXT_BUTTON_DELETE) ?>

</form>    
    
 
