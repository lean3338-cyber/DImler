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
			diapositiva.setAttribute("aria-hidden", oculta);
			diapositiva.inert = oculta;
		});
		botonesIndicadores.forEach(function(boton, posicion) {
			boton.setAttribute("aria-current", posicion === indiceActual ? "true" : "false");
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

document.addEventListener("DOMContentLoaded", function() {
	const CART_KEY = "teamdimler-cart";
	const ORDERS_KEY = "teamdimler-pedidos";
	const REGISTERED_KEY = "teamdimler-demo-registered";
	const formatCurrency = function(value) {
		return new Intl.NumberFormat("es-AR", {
			style: "currency",
			currency: "ARS",
			minimumFractionDigits: 2
		}).format(value);
	};

	const getCart = function() {
		try {
			const raw = localStorage.getItem(CART_KEY);
			return raw ? JSON.parse(raw) : [];
		} catch (error) {
			return [];
		}
	};

	const saveCart = function(items) {
		localStorage.setItem(CART_KEY, JSON.stringify(items));
	};

	const getCartCount = function() {
		return getCart().reduce(function(total, item) {
			return total + Number(item.quantity || 0);
		}, 0);
	};

	const setMessage = function(message, isError) {
		const mensaje = document.getElementById("mensaje-carrito");
		if (!mensaje) return;
		mensaje.textContent = message;
		mensaje.hidden = false;
		mensaje.classList.toggle("error", Boolean(isError));
		mensaje.classList.toggle("success", !isError);
	};

	const updateCartHeader = function() {
		const header = document.querySelector(".banner");
		if (!header) return;
		const nav = header.querySelector("nav ul");
		const oldNavCart = nav && nav.querySelector('a[href="carrito.html"]');
		if (oldNavCart) oldNavCart.closest("li").remove();

		let cartLink = header.querySelector(".cart-link");
		if (!cartLink) {
			cartLink = document.createElement("a");
			cartLink.href = "carrito.html";
			cartLink.className = "cart-link";
			cartLink.innerHTML = '<svg class="cart-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 4h2l2.2 10.1a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 1.9-1.4L22 8H6"/><circle cx="10" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg><span class="cart-label">Carrito</span>';
			const badge = document.createElement("span");
			badge.className = "cart-badge";
			badge.textContent = "0";
			cartLink.appendChild(badge);
			header.appendChild(cartLink);
		}

		const count = getCartCount();
		const badge = cartLink.querySelector(".cart-badge");
		if (badge) badge.textContent = String(count);
		cartLink.setAttribute("aria-label", "Carrito con " + count + " producto" + (count === 1 ? "" : "s"));
		cartLink.title = "Ver carrito";
	};

	updateCartHeader();
	window.addEventListener("storage", updateCartHeader);

	const formularios = document.querySelectorAll(".formulario-demo[data-demo='true']");
	formularios.forEach(function(formulario) {
		formulario.addEventListener("submit", function(event) {
			event.preventDefault();
			const mensaje = formulario.querySelector(".mensaje-formulario");
			if (!mensaje) return;

			const esLogin = formulario.querySelector("#email") && formulario.querySelector("#contrasena") && !formulario.querySelector("#nombre");
			const email = formulario.querySelector("#email");
			const password = formulario.querySelector("#contrasena");
			const confirmarPassword = formulario.querySelector("#confirmar-contrasena");
			const nombre = formulario.querySelector("#nombre");
			const aceptar = formulario.querySelector("input[type='checkbox']");

			let error = "";

			if (esLogin) {
				if (!email || !email.value.trim()) error = "Ingresá tu correo electrónico.";
				else if (!password || !password.value.trim()) error = "Ingresá tu contraseña.";
				else {
					sessionStorage.setItem("teamdimler-demo-authenticated", "true");
					mensaje.classList.remove("error");
					mensaje.classList.add("success");
					mensaje.textContent = "¡Bienvenido! Esta demo de login está lista para conectarse a tu backend.";
					mensaje.hidden = false;
					if (sessionStorage.getItem("teamdimler-return-to-checkout") === "true") {
						sessionStorage.removeItem("teamdimler-return-to-checkout");
						window.location.href = "carrito.html";
					}
					return;
				}
			} else {
				if (!nombre || !nombre.value.trim()) error = "Ingresá tu nombre.";
				else if (!email || !email.value.trim()) error = "Ingresá tu correo electrónico.";
				else if (!password || password.value.length < 6) error = "La contraseña debe tener al menos 6 caracteres.";
				else if (confirmarPassword && password.value !== confirmarPassword.value) error = "Las contraseñas no coinciden.";
				else if (aceptar && !aceptar.checked) error = "Debés aceptar los términos y condiciones.";
				else {
					localStorage.setItem(REGISTERED_KEY, "true");
					mensaje.classList.remove("error");
					mensaje.classList.add("success");
					mensaje.textContent = "¡Cuenta creada correctamente!";
					mensaje.hidden = false;
					if (sessionStorage.getItem("teamdimler-return-to-checkout") === "true") {
						window.location.href = "Login.html?destino=carrito.html";
					} else {
						window.location.href = "Login.html";
					}
					return;
				}
			}

			mensaje.classList.remove("success");
			mensaje.classList.add("error");
			mensaje.textContent = error;
			mensaje.hidden = false;
		});
	});

	document.querySelectorAll(".boton-agregar-carrito").forEach(function(boton) {
		boton.addEventListener("click", function() {
			const id = boton.dataset.productId;
			const nombre = boton.dataset.productName;
			const precio = Number(boton.dataset.productPrice || 0);
			const imagen = boton.dataset.productImage || "img/Generico.webp";
			const cart = getCart();
			const index = cart.findIndex(function(item) {
				return String(item.id) === String(id);
			});

			if (index >= 0) {
				cart[index].quantity += 1;
			} else {
				cart.push({
					id: String(id),
					name: nombre,
					price: precio,
					image: imagen,
					quantity: 1
				});
			}

			saveCart(cart);
			updateCartHeader();
			setMessage("Producto agregado al carrito.", false);
			boton.textContent = "Agregado";
			window.setTimeout(function() {
				boton.textContent = "Agregar al carrito";
			}, 1200);
		});
	});

	const carritoContenedor = document.getElementById("carrito-contenedor");
	if (carritoContenedor) {
		const renderCart = function() {
			const cart = getCart();
			const totalCarrito = document.getElementById("total-carrito");

			if (!cart.length) {
				carritoContenedor.innerHTML = '<section class="estado-vacio"><p>Tu carrito está vacío.</p><a class="boton-principal" href="index.html">Ver productos</a></section>';
				if (totalCarrito) totalCarrito.textContent = "Total: $0,00";
				return;
			}

			let total = 0;
			carritoContenedor.innerHTML = cart.map(function(item) {
				total += Number(item.price) * Number(item.quantity);
				return '<article class="pedido-card carrito-item">'
					+ '<img src="' + item.image + '" alt="' + item.name + '">'
					+ '<div>'
					+ '<h3>' + item.name + '</h3>'
					+ '<p>' + formatCurrency(Number(item.price)) + '</p>'
					+ '<div class="cantidad-control">'
					+ '<button type="button" data-action="decrease" data-id="' + item.id + '">−</button>'
					+ '<input type="number" min="1" max="20" value="' + item.quantity + '" data-id="' + item.id + '">'
					+ '<button type="button" data-action="increase" data-id="' + item.id + '">+</button>'
					+ '</div>'
					+ '</div>'
					+ '<div class="carrito-acciones">'
					+ '<p><strong>' + formatCurrency(Number(item.price) * Number(item.quantity)) + '</strong></p>'
					+ '<button class="boton-texto" type="button" data-action="remove" data-id="' + item.id + '">Quitar</button>'
					+ '</div>'
					+ '</article>';
			}).join("");

			if (totalCarrito) totalCarrito.textContent = "Total: " + formatCurrency(total);

			carritoContenedor.querySelectorAll("[data-action]").forEach(function(element) {
				element.addEventListener("click", function() {
					const cartItems = getCart();
					const itemId = element.dataset.id;
					const itemIndex = cartItems.findIndex(function(item) {
						return String(item.id) === String(itemId);
					});

					if (itemIndex === -1) return;

					if (element.dataset.action === "remove") {
						cartItems.splice(itemIndex, 1);
					} else {
						const accion = element.dataset.action;
						cartItems[itemIndex].quantity = accion === "increase"
							? Math.min(20, Number(cartItems[itemIndex].quantity) + 1)
							: Math.max(1, Number(cartItems[itemIndex].quantity) - 1);
					}

					saveCart(cartItems);
					updateCartHeader();
					renderCart();
				});
			});

			carritoContenedor.querySelectorAll("input[data-id]").forEach(function(input) {
				input.addEventListener("change", function() {
					const cartItems = getCart();
					const itemId = input.dataset.id;
					const itemIndex = cartItems.findIndex(function(item) {
						return String(item.id) === String(itemId);
					});

					if (itemIndex === -1) return;

					const nuevaCantidad = Number(input.value);
					cartItems[itemIndex].quantity = Number.isFinite(nuevaCantidad) ? Math.min(20, Math.max(1, nuevaCantidad)) : 1;
					saveCart(cartItems);
					updateCartHeader();
					renderCart();
				});
			});
		};

		renderCart();

		const confirmarPedido = document.getElementById("confirmar-pedido");
		if (confirmarPedido) {
			confirmarPedido.addEventListener("click", function() {
				const cart = getCart();
				if (!cart.length) {
					setMessage("Tu carrito está vacío.", true);
					return;
				}
				if (sessionStorage.getItem("teamdimler-demo-authenticated") !== "true") {
					sessionStorage.setItem("teamdimler-return-to-checkout", "true");
					const paginaAcceso = localStorage.getItem(REGISTERED_KEY) === "true" ? "Login.html" : "registro.html";
					window.location.href = paginaAcceso + "?destino=carrito.html";
					return;
				}

				const pedidos = JSON.parse(localStorage.getItem(ORDERS_KEY) || "[]");
				const total = cart.reduce(function(sum, item) {
					return sum + Number(item.price) * Number(item.quantity);
				}, 0);

				pedidos.unshift({
					id: Date.now(),
					fecha: new Date().toISOString(),
					items: cart,
					total: total
				});

				localStorage.setItem(ORDERS_KEY, JSON.stringify(pedidos));
				localStorage.removeItem(CART_KEY);
				updateCartHeader();
				window.location.href = "mis-pedidos.html";
			});
		}
	}

	const pedidosContenedor = document.getElementById("pedidos-contenedor");
	if (pedidosContenedor) {
		const pedidos = JSON.parse(localStorage.getItem(ORDERS_KEY) || "[]");
		if (!pedidos.length) {
			pedidosContenedor.innerHTML = '<section class="estado-vacio"><p>Todavía no realizaste ningún pedido.</p><a class="boton-principal" href="index.html">Explorar productos</a></section>';
			return;
		}

		pedidosContenedor.innerHTML = pedidos.map(function(pedido) {
			const items = pedido.items.map(function(item) {
				return '<li>' + item.name + ' x' + item.quantity + ' — ' + formatCurrency(Number(item.price) * Number(item.quantity)) + '</li>';
			}).join("");

			return '<article class="pedido-card">'
				+ '<h3>Pedido #' + pedido.id + '</h3>'
				+ '<p><strong>Fecha:</strong> ' + new Date(pedido.fecha).toLocaleString("es-AR", {dateStyle: "short", timeStyle: "short"}) + '</p>'
				+ '<ul>' + items + '</ul>'
				+ '<p><strong>Total:</strong> ' + formatCurrency(Number(pedido.total)) + '</p>'
				+ '</article>';
		}).join("");
	}

	const contenedor = document.getElementById("detalle-producto");
	if (!contenedor) return;

	const productos = {
		oferta: {
			titulo: "Ofertas en útiles",
			categoria: "Librería",
			foto: "img/gener - ofert.webp",
			descripcion: "Selección de lápices, marcadores y útiles escolares.",
			precio: 1800,
			volver: "libreria.html",
			textoVolver: "Ver librería"
		},
		libreria: {
			titulo: "Combo de librería",
			categoria: "Librería",
			foto: "img/generico libreria.webp",
			descripcion: "Incluye lapicera, goma, tijera y cinta.",
			precio: 2600,
			volver: "libreria.html",
			textoVolver: "Ver librería"
		},
		costura: {
			titulo: "Kit de costura",
			categoria: "Costura",
			foto: "img/Generico.webp",
			descripcion: "Kit con hilos, agujas, tijeras y accesorios de costura.",
			precio: 3200,
			volver: "index.html#todos",
			textoVolver: "Volver a productos"
		},
		"flores-crochet": {
			titulo: "Flores de crochet",
			categoria: "Crochet",
			foto: "img/Generico.webp",
			descripcion: "Set artesanal con flores tejidas a mano para decorar, regalar y personalizar tus espacios.",
			precio: 2400,
			volver: "crochet.html",
			textoVolver: "Ver crochet"
		},
		"manta-crochet": {
			titulo: "Manta pequeña",
			categoria: "Crochet",
			foto: "img/generico libreria.webp",
			descripcion: "Pequeña manta hecha a mano en colores suaves, ideal para detalle o decoración.",
			precio: 3900,
			volver: "crochet.html",
			textoVolver: "Ver crochet"
		},
		"porta-celular": {
			titulo: "Porta celular",
			categoria: "Crochet",
			foto: "img/gener - ofert.webp",
			descripcion: "Porta celular artesanal con diseño simple y práctico para uso diario.",
			precio: 2100,
			volver: "crochet.html",
			textoVolver: "Ver crochet"
		}
	};

	const clave = new URLSearchParams(window.location.search).get("producto");
	const producto = productos[clave];
	if (!producto) {
		contenedor.textContent = "No se encontró el producto solicitado.";
		return;
	}

	const imagen = document.createElement("img");
	imagen.src = producto.foto;
	imagen.alt = producto.titulo;
	const informacion = document.createElement("div");
	informacion.className = "detalle-info";
	const categoria = document.createElement("p");
	categoria.className = "detalle-categoria";
	categoria.textContent = producto.categoria;
	const titulo = document.createElement("h3");
	titulo.textContent = producto.titulo;
	const descripcion = document.createElement("p");
	descripcion.textContent = producto.descripcion;
	const precio = document.createElement("p");
	precio.className = "producto-precio";
	precio.textContent = formatCurrency(producto.precio);
	const volver = document.createElement("a");
	volver.className = "boton-principal";
	volver.href = producto.volver;
	volver.textContent = producto.textoVolver;

	const botonAgregar = document.createElement("button");
	botonAgregar.type = "button";
	botonAgregar.className = "boton-principal boton-agregar-carrito";
	botonAgregar.dataset.productId = clave;
	botonAgregar.dataset.productName = producto.titulo;
	botonAgregar.dataset.productPrice = String(producto.precio);
	botonAgregar.dataset.productImage = producto.foto;
	botonAgregar.textContent = "Agregar al carrito";
	botonAgregar.addEventListener("click", function() {
		const cart = getCart();
		const index = cart.findIndex(function(item) {
			return String(item.id) === String(clave);
		});
		if (index >= 0) {
			cart[index].quantity += 1;
		} else {
			cart.push({
				id: String(clave),
				name: producto.titulo,
				price: Number(producto.precio),
				image: producto.foto,
				quantity: 1
			});
		}
		saveCart(cart);
		updateCartHeader();
		setMessage("Producto agregado al carrito.", false);
		botonAgregar.textContent = "Agregado";
		window.setTimeout(function() {
			botonAgregar.textContent = "Agregar al carrito";
		}, 1200);
	});

	informacion.append(categoria, titulo, descripcion, precio, botonAgregar, volver);
	contenedor.replaceChildren(imagen, informacion);
});
