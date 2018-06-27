var wemForms = {};
$(document).ready(function(){
	$('.ce_form form').each(function(){
		var form = $(this);
		var formID = parseInt($(this).find('input[name="FORM_SUBMIT"]').first().attr('value').replace('auto_form_', ''));
		$.post(
			window.location.pathname
			,{
				"TL_AJAX": 1
				,"module": "wem-contao-form-submissions"
				,"form": formID
				,"action": "checkForm"
				,"REQUEST_TOKEN": $(this).find('input[name="REQUEST_TOKEN"]').first().attr('value')
			}
			,function(data){
				if("success" != data.status)
					console.log("wem-contao-form-submissions - erreur : "+data.msg);
				else if(1 == data.tobuild)
					wemForms[formID] = new wemForm(formID, form);
			}
			,"json"
		);
	});
});

class wemForm{
	constructor(formID, form){
		var wemForm = this
		wemForm.formID = formID;
		wemForm.el = $(form);
		wemForm.fields = {};
		wemForm.logs = [];

		wemForm.el.find('input, select, textarea').bind('focus', function(e){
			wemForm.logs.push({
				"createdAt": Math.floor(Date.now() / 1000)
				,"type": "focus"
				,"log": "L'utilisateur a sélectionné le champ "+$(this).attr('name')
			});
		})
		.bind('blur',function(e){
			wemForm.logs.push({
				"createdAt": Math.floor(Date.now() / 1000)
				,"type": "blur"
				,"log": "L'utilisateur est sorti du champ : "+$(this).attr('name')+" avec la valeur : "+$(this).val()
			});

			wemForm.fields[parseInt($(this).attr('id').replace('ctrl_', ''))] = $(this).val();
		});

		wemForm.el.bind('submit', function(e){
			e.preventDefault();
			// prevent any additional submission while processing
			wemForm.el.find('.submit').bind('click',function(e){e.preventDefault()});

			wemForm.logs.push({
				"createdAt": Math.floor(Date.now() / 1000)
				,"type": "submit"
				,"log": "L'utilisateur a validé le formulaire"
			});

			wemForm.storeSubmission().then(function(data){
				wemForm.submissionID = data.submission;
				console.log(data);

				wemForm.storeLogs().then(function(data){
					console.log(data);
					wemForm.el.unbind('submit');
					wemForm.el.submit();
				});
			}).catch(function(data){
				console.log("Submission failed: "+data);
				// allow user to submit again
				wemForm.el.find('.submit').unbind('click');
			});
		});
	}

	storeSubmission() {
		var wemForm = this;
		return new Promise(function(resolve,reject){
			$.post(
				window.location.pathname
				,{
					"TL_AJAX": 1
					,"REQUEST_TOKEN": wemForm.el.find('input[name="REQUEST_TOKEN"]').first().attr('value')
					,"module": "wem-contao-form-submissions"
					,"form": wemForm.formID
					,"action": "submit"
					,"fields":wemForm.fields
				}
				,function(data){
					if("success" != data.status)
						reject(data.msg);
					else
						resolve(data);
				}
				,"json"
			);
		});
	}

	storeLogs() {
		var wemForm = this;
		return new Promise(function(resolve,reject){
			if(0 == wemForm.logs.length)
				reject("Aucun logs");

			$.post(
				window.location.pathname
				,{
					"TL_AJAX": 1
					,"REQUEST_TOKEN": wemForm.el.find('input[name="REQUEST_TOKEN"]').first().attr('value')
					,"module": "wem-contao-form-submissions"
					,"form": wemForm.formID
					,"submission": wemForm.submissionID
					,"action": "log"
					,"logs":wemForm.logs
				}
				,function(data){
					if("success" != data.status)
						reject(data.msg);
					else
						resolve(data);
				}
				,"json"
			);
		});
	}
}