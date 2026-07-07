<nav id="usernav" class="navbar navbar-default">
	<div class="navbar-global theme-default">
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
					<span class="sr-only">Toggle Navigation</span>
					<i class="glyph glyph-hamburger"></i>
				</button>

				<a href="https://www.cloudveil.org" class="navbar-brand">
					<img src="https://www.cloudveil.org/wp-content/uploads/2016/06/Small-Logo.png" alt="CloudVeil" height="23" />
				</a>
			</div>

			<div class="collapse navbar-collapse" id="navbar-collapse">
				<ul class="nav navbar-nav">
					<li class="active"><a href="#">Dashboard</a></li>
					<li id="admin_link" class="hidden"><a href="admin">Admin</a></li>
				</ul>
				<ul class="nav navbar-nav navbar-right">
					<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Logged in as: {{ Auth::user()->name }} <span class="glyph glyph-chevron-down"></span></a>
						<ul class="dropdown-menu">
							<li><a style="cursor: pointer;" onclick="$('#change_password_modal').modal('show');" id="change_password_button">Change Password</a></li>
							<li><a href="#" id="logout_button">Logout</a></li>
						</ul>
					</li>
				</ul>
			</div>
		</div>
	</div>
</nav>

<div class="modal" id="change_password_modal"
		tabindex="-1" role="dialog"
		aria-labelledby="password_title" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form>
				<div class="modal-header">
					<h4 class="modal-title" id="password_title">
						Change Password
					</h4>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label for="current_password">Current Password</label>
						<input type="password" class="form-control" id="current_password" placeholder="Password" />
					</div>

					<div class="form-group">
						<label for="new_password">New Password</label>
						<input type="password" class="form-control" id="new_password" placeholder="New Password" />
					</div>

					<div class="form-group">
						<label for="confirm_password">Confirm Password</label>
						<input type="password" class="form-control" id="confirm_password" placeholder="Confirm Password" />
					</div>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Change</button>
					<button type="button" class="btn btn-info" data-dismiss="modal">Cancel</button>
				</div>
			</form>
		</div>
	</div>
</div>
