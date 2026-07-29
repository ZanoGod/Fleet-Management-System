            </main>
            </div>
            </div>

            <script src="<?= e(asset_url('assets/vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
            <script src="<?= e(asset_url('assets/js/app.js')) ?>"></script>

            </body>

            <script>
                const loader = document.getElementById("pageLoader");


                document.querySelectorAll(
                        ".sidebar-link[href]"
                    )
                    .forEach(link => {


                        link.addEventListener(
                            "click",
                            function(e) {


                                const url = this.href;


                                if (
                                    this.classList.contains("logout-link")
                                ) {

                                    return;

                                }


                                e.preventDefault();


                                /*
                                Active state immediately
                                */

                                document.querySelectorAll(
                                        ".sidebar-link"
                                    )
                                    .forEach(item => {

                                        item.classList.remove(
                                            "is-active"
                                        );

                                    });


                                this.classList.add(
                                    "is-active"
                                );



                                /*
                                Show skeleton
                                */

                                document.body.classList.add(
                                    "is-loading"
                                );



                                /*
                                Small delay so animation can render
                                */

                                setTimeout(() => {

                                    window.location.href = url;


                                }, 400);



                            });


                    });

                // Show skeleton
                document.documentElement.classList.add("is-loading"); // Use documentElement (html)

                setTimeout(() => {
                    window.location.href = url;
                }, 400);

                // Update your window load event
                window.addEventListener("load", () => {
                    document.documentElement.classList.remove("is-loading"); // Match the change above
                    const content = document.querySelector('.app-content');
                    if (content) {
                        content.classList.add('is-visible');
                    }
                });

                document.getElementById('resetFilters').addEventListener('click', function() {
                    const form = this.closest('form');

                    form.querySelectorAll('input[type="text"]').forEach(input => {
                        input.value = '';
                    });

                    form.querySelectorAll('select').forEach(select => {
                        select.selectedIndex = 0;
                    });
                });
            </script>

            </html>