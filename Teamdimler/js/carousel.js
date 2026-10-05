document.addEventListener("DOMContentLoaded", function() {
	const carrusel = document.getElementById("ofertas");
	if (!carrusel) return;

	const pista = carrusel.querySelector(".ofertas-track");
	const diapositivas = Array.from(pista.querySelectorAll(".oferta-slide"));
	const indicadores = carrusel.querySelector(".ofertas-indicadores");
	const anterior = carrusel.querySelector(".ofertas-anterior");
	const siguiente = carrusel.querySelector(".ofertas-siguiente");
	let indiceActual = 0;
	let temporizador = null;

	diapositivas.forEach(function(diapositiva, indice) {
		const indicador = document.createElement("button");
		indicador.type = "button";
		indicador.setAttribute("aria-label", `Mostrar oferta ${indice + 1}`);
		indicador.setAttribute("aria-current", indice === 0 ? "true" : "false");
		indicador.addEventListener("click", function() {
			mostrarDiapositiva(indice);
			iniciarAvance();
		});
		indicadores.appendChild(indicador);
	});

	const botonesIndicadores = Array.from(indicadores.querySelectorAll("button"));

	function mostrarDiapositiva(indice) {
		indiceActual = (indice + diapositivas.length) % diapositivas.length;
		pista.style.transform = `translateX(-${indiceActual * 100}%)`;

		diapositivas.forEach(function(diapositiva, posicion) {
			const oculta = posicion !== indiceActual;
			diapositiva.setAttribute("aria-hidden", String(oculta));
			diapositiva.inert = oculta;
		});
		botonesIndicadores.forEach(function(boton, posicion) {
			boton.setAttribute("aria-current", String(posicion === indiceActual));
		});
	}

	function detenerAvance() {
		window.clearInterval(temporizador);
		temporizador = null;
	}

	function iniciarAvance() {
		detenerAvance();
		if (diapositivas.length < 2 || document.hidden || carrusel.matches(":hover")) return;
		temporizador = window.setInterval(function() {
			mostrarDiapositiva(indiceActual + 1);
		}, 5000);
	}

	anterior.addEventListener("click", function() {
		mostrarDiapositiva(indiceActual - 1);
		iniciarAvance();
	});
	siguiente.addEventListener("click", function() {
		mostrarDiapositiva(indiceActual + 1);
		iniciarAvance();
	});
	carrusel.addEventListener("mouseenter", detenerAvance);
	carrusel.addEventListener("mouseleave", iniciarAvance);
	document.addEventListener("visibilitychange", function() {
		if (document.hidden) detenerAvance();
		else iniciarAvance();
	});
	mostrarDiapositiva(0);
	iniciarAvance();
});
