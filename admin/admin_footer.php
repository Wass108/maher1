            </main><!-- Fermeture de la balise main -->
        </div><!-- Fermeture de la div row -->
    </div><!-- Fermeture de la div container-fluid -->

    <script src="../js/jquery-2.1.4.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script>
        // Initialiser les composants bootstrap qui nécessitent JavaScript
        $(document).ready(function() {
            // Activer les dropdowns
            $('.dropdown-toggle').dropdown();
            
            // Activer les tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Activer les popovers
            $('[data-toggle="popover"]').popover();
        });
    </script>
</body>
</html>