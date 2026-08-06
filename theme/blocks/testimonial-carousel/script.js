import Swiper from "swiper";
import { Pagination } from "swiper/modules";

document.querySelectorAll(".testimonial-carousel-swiper").forEach((el) => {
	new Swiper(el, {
		modules: [Pagination],
		loop: true,
		autoHeight: true,
		slidesPerView: 1,
		spaceBetween: 32,
		pagination: {
			el: el.querySelector(".swiper-pagination"),
			clickable: true,
		},
	});
});
