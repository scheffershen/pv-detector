import Shepherd from 'shepherd.js';

$( () => {
	const tour = new Shepherd.Tour({
	  useModalOverlay: true,
	  defaultStepOptions: {
	    classes: 'shadow-md bg-purple-dark',
	    scrollTo: true
	  }
	});

});