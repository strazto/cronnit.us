$(".text-thumbnail-toggle").on("click", (e) => {
  $(e.currentTarget).siblings(".text-thumbnail-body").collapse('toggle');
});

