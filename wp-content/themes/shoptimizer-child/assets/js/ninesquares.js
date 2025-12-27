document.querySelectorAll('.search-button a').forEach(function(element) {
	
	element.addEventListener('click', function(e) {
		e.preventDefault();
		element.closest('.search-button').parentElement.querySelector('.search-popup').classList.toggle('active')
		document.querySelector('body').classList.toggle('lock')
	})
})

document.addEventListener('mouseup', function(e) {

	let container = document.querySelector('.search-popup > div');
	if (document.querySelector('.search-popup').classList.contains('active')) {
		
		if (!e.target.closest('a') && !container.contains(e.target)) {
			document.querySelector('.search-button a').click();
		}
	}
});