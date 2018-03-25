		<script src="http://code.jquery.com/jquery-2.2.1.min.js" ></script>
		<script>
		

			$.ajax
			({
				type: "POST",
				url: "http://localhost/api-client/index.php",
				data: {"return_url":"http://localhost/api-client/index.php"},
				success: function (data) {
				console.log(data); 
				$(".form").html(data);
				}
			})


		
		</script>
		<div class="form">
			
		</div>