// inputs 
	EratagsForm::get_instance()->input([
		'type' 	=> 'checkbox',
		'class' => 'tags-class',
		'id'	=> 'checkbox-data'
	]); 

	// Textarea
	EratagsForm::get_instance()->textarea([
		'value' => EratagsHelper::get_instance()->get_option('redirect_url'),
		'id' 	=> 'tags-id' ,
		'name' 	=> 'eratags-textarea'
	]);
	
	// Select box
	EratagsForm::get_instance()->select(['class'=>'selector'], array(
		array( 'value' => 'option_1', 'text' => 'Option 1' ),
		array( 'value' => 'option_2', 'text' => 'Option 2' ),
		array( 'value' => 'option_3', 'text' => 'Option 3', 'selected' ),
		array( 'value' => 'option_4', 'text' => 'Option 4' ),
	));