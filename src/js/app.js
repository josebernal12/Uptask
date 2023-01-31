// alert('hola mundo')
const mobileMenuBtn = document.querySelector('#mobile_menu')
const cerrarMenuBtn = document.querySelector('#cerrar')
const sidebar = document.querySelector('.sidebar')

if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', function () {
        sidebar.classList.add('mostrar')
    })
}
if (cerrarMenuBtn) {
    cerrarMenuBtn.addEventListener('click', function () {
        sidebar.classList.add('ocultar')

        setTimeout(() => {
            sidebar.classList.remove('mostrar')
            sidebar.classList.remove('ocultar')

        }, 1000);
    })
}

//elimina la clase de monstrar en un tamaño de table y mayores
const anchoPantalla = document.body.clientWidth
window.addEventListener('resize', function () {
    const anchoPantalla = document.body.clientWidth
    if (anchoPantalla >= 768) {
        sidebar.classList.remove('mostrar')
    }
})