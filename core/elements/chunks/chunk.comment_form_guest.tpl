<h4 id="comment-new-link">
    <a href="#" class="btn btn-default">[[%ticket_comment_create]]</a>
</h4>

<div id="comment-form-placeholder">
    <form id="comment-form" action="" method="post" class="well">
        <input type="hidden" name="thread" value="[[+thread]]"/>
        <input type="hidden" name="parent" value="0"/>
        <input type="hidden" name="id" value="0"/>
        <input type="hidden" name="form_key" value="[[+formkey]]">

        <div class="form-group">
            <label for="comment-name">[[%ticket_comment_name]]</label>
            <input type="text" name="name" value="[[+name]]" id="comment-name" class="form-control"/>
            <span class="error"></span>
        </div>

        <div class="form-group">
            <label for="comment-email">[[%ticket_comment_email]]</label>
            <input type="text" name="email" value="[[+email]]" id="comment-email" class="form-control"/>
            <span class="error"></span>
        </div>

        <!-- вкладки -->
        <ul class="tickets-editor-nav tickets-editor-nav-tabs" role="tablist">
            <li class="tickets-editor-nav-item">
                <input type="button" value="[[%ticket_comment_edit]]" class="tickets-editor-nav-link active" id="tickets-editor-tab-edit" data-toggle="tab" data-href="#comment-edit" role="tab">
            </li>
            <li class="tickets-editor-nav-item">
                <input type="button" value="[[%ticket_comment_preview]]" class="tickets-editor-nav-link preview" id="tickets-editor-tab-preview" data-toggle="tab" data-href="#comment-preview" role="tab">
            </li>
        </ul>

        <div class="tickets-editor-tab-content">
            <div class="tickets-editor-tab-pane fade show active" id="comment-edit" role="tabpanel">
                <div class="form-group">
                    <label for="comment-editor"></label>
                    <textarea name="text" id="comment-editor" cols="30" rows="10" class="form-control"></textarea>
                    <span class="error" id="text-error"></span>
                </div>
            </div>
            <div class="tickets-editor-tab-pane fade" id="comment-preview" role="tabpanel">
                <div id="comment-preview-placeholder" class="well"></div>
            </div>
        </div>

        [[+allowFiles:is=`1`:then=`
            [[%ticket_comment_upload_auth]]
        `]]

        [[+captcha]]

        <div class="form-actions">
            <input type="submit" class="tickets-btn btn btn-primary submit" value="[[%ticket_comment_save]]"
                   title="Ctrl + Shift + Enter"/>
            <span class="time"></span>
        </div>
    </form>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const tabLinks = document.querySelectorAll(".tickets-editor-nav-tabs .tickets-editor-nav-link");
  const tabPanes = document.querySelectorAll(".tickets-editor-tab-pane");
  const previewBtn = document.querySelector("#comment-form .preview");

  tabLinks.forEach(link => {
    link.addEventListener("click", e => {
      // снять active со всех табов и панелей
      tabLinks.forEach(l => l.classList.remove("active"));
      tabPanes.forEach(p => {
        p.classList.remove("active", "show");
      });

      // активировать выбранную вкладку
      link.classList.add("active");
      const target = document.querySelector(link.getAttribute("data-href"));
      if (target) target.classList.add("active", "show");

      // если открыли превью → вызвать старую логику
      if (link.id === "tickets-editor-tab-preview" && previewBtn) {
        previewBtn.click();
      }
    });
  });
});
</script>
<style>
/* контейнер навигации */
.tickets-editor-nav-tabs {
  border: 1px solid #ddd;
  display: flex;
  margin-bottom: 0;
  list-style: none;
  padding-left: 0;
	font-size: 1rem;
}

/* ссылка вкладки */
.tickets-editor-nav-tabs .tickets-editor-nav-link {
	display: block !important;
	padding: .75em 1em .55rem 1em;
	margin-right: 2px;
	text-decoration: none;
	color: #555 !important;
	cursor: pointer;
	text-transform: uppercase;
	font-size: .8rem;
	outline: none;
	border: none;
	display: inline-block;
	background: #fff !important;
}

/* активная вкладка */
.tickets-editor-nav-tabs .tickets-editor-nav-link.active {
  color: #000 !important;
  background-color: #ddd !important;
	font-weight: bold !important;
}

/* содержимое вкладок */
.tickets-editor-tab-content {
  border: 1px solid #ddd;
  border-top: none;
  padding: 10px;
	padding-top: 2px;
  background: #fff;
}

.tickets-editor-tab-pane {
  display: none;
}

.tickets-editor-tab-pane.active,
.tickets-editor-tab-pane.show {
  display: block;
}
</style>