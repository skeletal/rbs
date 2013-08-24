// Self explanatory
function createHiddenElement(name, value) {
	var element = document.createElement('input');

	element.type = 'hidden';
	element.name = name;
	element.value = value;

	return element;
}
