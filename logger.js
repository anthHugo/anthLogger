// Fichier UTF-8 (é)
// Objet statique commun à l'application, pas utile d'instancier 

/**
*	---------------- Changelog -----------------------
*
<<<<<<< HEAD
=======
* 	- CHANGEMENT DU CHANGELOG -
>>>>>>> origin/master
*	V0.2 - H - 24/07/2014 (Domicile Pneu)
*	- Ajout d'un mini objet gestionScrollbar afin de bloquer le scroll lors de l'affichage des boites de confirmation
*	- /!\ Attention si vous souhaitez bloquer la scrollbar toutes les autres le seront aussi /!\
	- Gestion d'une durée pour effacer le message
*
**/
var Logger = {

	version : 0.1,
	arrayMessage : [],	// Tableau d'objet de message

	parametres : {
		affichage : 'alert',
		container : 'logger_container',
		duration :  3500,
		autoClose : true,
		template  :  '<div class="logger_message {type}" id="{id}"><span class="logger_texte">{message}</span><span class="logger_close">×</span></div>',
		dialog : {
			initDialog : true,
			container : 'logger_confirm',
			classButton : 'button_confirm'
		}
	},

	jQueryDialog : null,

	callBack : function(){},

	init : function(params)
	{
		// Prise en charge des parametres
		if(typeof(params) == 'object')
			this.parametres = jQuery.extend(this.parametres, params);


		if(this.parametres.dialog.initDialog)
			this.initDialog();
	},

	initDialog : function()
	{
		dialogOption = this.parametres.dialog;
		this.jQueryDialog = jQuery('#'+dialogOption.container);
		
		// Si la boite de dialog est inexistante on la creer
		if(this.jQueryDialog.length == 0)
		{
			jQuery('body').append('<div id="'+dialogOption.container+'"></div>');
			
			// Initialisation d'une boite de dialog
			jQuery('#'+dialogOption.container).dialog({
		        autoOpen : false,
		        resizable: false,
		        draggable : false,
		        modal : true,
		        width:550,
		        dialogClass : dialogOption.container +' alerte_box',
		        open : function(){ GestionScrollBar.disable_scrolling(); },
		        close : function(){ GestionScrollBar.enable_scrolling(); },
		        buttons: [{ 
			        	text :'Oui',
			        	id : 'logger_ok',
			        	'class' : dialogOption.classButton,
						click : function() {}
		        	},
		        	{
		            	text : 'Non',
		            	id : 'logger_annuler',
		            	'class' : dialogOption.classButton,
						click : function() {}
		            }
		        ]
		    });	

		    this.jQueryDialog = jQuery('#'+dialogOption.container);

		    // Au resize de la page on re-centre la boite de confirmation
		    jQuery(window).bind('resize', function(){
		    	jQuery('#'+dialogOption.container).dialog({ position : { my : 'center center', at : 'center center'} });
		    });

		}
		return this;
	
	},

	ajoutMessage : function(message, type, duration) 
	{
		if(typeof(message) == 'undefined')
			return false;

		if(typeof(type) == 'undefined')
			type = 'erreur';

		if(typeof(duration) == 'undefined')
			duration = false;

		objMessage = {texte : message, type : type, duration : duration};

		identifiant = message.length + type.length + type;
	
		this.arrayMessage[identifiant] = objMessage;
	},

	ajoutListeMessage : function(jsonMessage)
	{
		if(typeof(jsonMessage) == 'object')
		{
			for(var i in jsonMessage)
			{
				identifiant = jsonMessage[i].texte.length + jsonMessage[i].type.length + jsonMessage[i].type;
				this.arrayMessage[identifiant] = jsonMessage[i];					
			}			
		}
	},	

	afficheMessage : function(callBack)
	{
		if(this.parametres.affichage == 'alert')
		{
			message = '';
			for(var i in this.arrayMessage)
			{
				message += this.arrayMessage[i].texte + '\r\n';
			}
			alert(message);
			Logger.viderMessage();
		}
		else if(this.parametres.affichage == 'top')
		{
			// On creer la div qui contient les messages
			if(jQuery('#'+this.parametres.container).length == 0)
				jQuery('body').prepend('<div id="'+this.parametres.container+'"></div>');
			
			var divContainer = jQuery('div#'+this.parametres.container);

			if(typeof(this.arrayMessage) == 'object')
			{
				// On affiche tous les messages present dans le tableau
				for(var identifiant in this.arrayMessage)
				{	
					// Si le message n'est pas deja afficher
					if(jQuery('.logger_message#'+identifiant).length == 0)
					{
						var html = this.parametres.template;
						//console.log(this.arrayMessage[identifiant].texte);
						var texte = this.arrayMessage[identifiant].texte.toString().replace(/\n/g, '<br>');

						var duration = this.arrayMessage[identifiant].duration;

						html = html.replace(/{message}/g, texte);
						html = html.replace(/{type}/g, this.arrayMessage[identifiant].type);
						html = html.replace(/{id}/g, identifiant);

						divContainer.prepend(html);

						notification = jQuery('.logger_message#'+identifiant);

						// On attache les differents evenement à la notification
						notification.slideDown(500);
						
						// Fermeture automatique
						if(this.parametres.autoClose && typeof(duration) == 'boolean')
						{
							notification.delay(this.parametres.duration).slideUp(function(){
								jQuery(this).remove();
								Logger.supprimerMessage(identifiant);
							});							
						}
						else if(duration > 0)
						{
							notification.delay(duration).slideUp(function(){
								jQuery(this).remove();
								Logger.supprimerMessage(identifiant);
							});			
						}

						// au click sur la croix
						notification.find('.logger_close').bind('click', function(){
							jQuery(this).parent().stop().slideUp(function(){
								jQuery(this).remove();
								Logger.supprimerMessage(identifiant);
							});
						});

						// au click sur la notification
						notification.bind('click', function(){
							jQuery(this).stop().slideUp(function(){
								jQuery(this).remove();
								Logger.supprimerMessage(identifiant);
							});
						});							
					}
				}			
			}
		}
		
		if(typeof(callBack) == 'function')
		{
			this.callBack = callBack;
		}

		this.callBack();
	},

	supprimerMessage : function(identifiant) { return delete this.arrayMessage[identifiant]; },
	viderMessage : function() { this.arrayMessage = []; },
	confirm : function(message, callBack)
	{
		this.initDialog();
		this.jQueryDialog.html('<p>'+message+'</p>');
		this.jQueryDialog.dialog('open');

		jQuery('.'+this.parametres.dialog.classButton).unbind('click').bind('click', function(event){
			Logger.jQueryDialog.dialog('close');
			
			if(jQuery(this).attr('id') == 'logger_ok')
				callBack(true);
			else
				callBack(false);
		});
	}
}; 




// Mini objet de controle de la scrollbar de fenetre
var GestionScrollBar = {
	scrollEventKeys : [33, 34, 35, 36, 37, 38, 39, 40],
	$window : jQuery(window),
    $document : jQuery(document),

    disable_scrolling : function() {
        var t = this;
        t.$window.on("mousewheel.GestionScrollBar DOMMouseScroll.GestionScrollBar", this._handleWheel);
        t.$document.on("mousewheel.GestionScrollBar touchmove.GestionScrollBar", this._handleWheel);
        t.$document.on("keydown.GestionScrollBar", function(event) {
            t._handleKeydown.call(t, event);
        });
    },

    enable_scrolling : function() {
        var t = this;
        t.$window.off(".GestionScrollBar");
        t.$document.off(".GestionScrollBar");
    },

    _handleKeydown : function(event) {
        for (var i = 0; i < this.scrollEventKeys.length; i++) {
            if (event.keyCode === this.scrollEventKeys[i]) {
                event.preventDefault();
                return;
            }
        }
    },

    _handleWheel : function(event) {
        event.preventDefault();
    }    
}