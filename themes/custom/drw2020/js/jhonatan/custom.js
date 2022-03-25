//verificamos que se haya cargado completo jquery
jQuery("document").ready(function()
{


	//ejecutamos el zoom
	jQuery("#zoom_03").elevateZoom();

	//obtenemos todos los enlaces (a) que están dentro de id galeria
	jQuery("#galeria a").click(function(){

	//creamos una variable para obtener la imagen que aparecerá de tamaño grande
	var dataimagen= jQuery(this).attr("data-image");

    //verificamos si recibimos una imagen indefinida
	if (dataimagen===undefined) {

 		console.log("el valor es:"+dataimagen+".");

	}
	//si no es indefinida
	else {

		//creamos una variable para obtener la imagen que aparecerá cuando pongamos el mouse por encima
		var dataimagenzoom= jQuery(this).attr("data-zoom-image");

		//hacemos un efecto llamado fadeout para que desaparezca todo lo que está dentro del divimagengrande
		jQuery("#divimagengrande").fadeOut(function(){

			//dentro del div divimagengrande sustituimos el html y lo reconstruimos con los datos de las variables recibidas. Si no hacemos esto, aunque la imagen grande se sustituirá, el zoom, se quedará con la primera imagen.
			jQuery("#divimagengrande").html('<img id="zoom_03" class="img-responsive" src="'+dataimagen+'" data-zoom-image="'+ dataimagenzoom+'"/>');

			//ejecutamos el elevatezoom a la imagen que tiene el id zoom_03.
			jQuery("#zoom_03").elevateZoom();

			//realizamos un fadein para que aparezca todo lo que está dentro del id divimagengrande
			jQuery("#divimagengrande").fadeIn();

		});
		//realizamos un return false, para que el enlace no se ejecute
		return false;
	}

	});

});
