/*
 * Tickets (Vanilla JS version)
 * Drop-in replacement for the old jQuery-based Tickets front-end.
 *
 * Dependencies removed:
 * - jQuery, jQuery Form (ajaxSubmit), jGrowl, Sisyphus, markItUp
 *
 * Optional libs still supported (auto-loaded if available/missing):
 * - Google Code Prettify (prettyPrint)
 * - SortableJS (pure JS)
 *
 * Public API: new TicketsApp(TicketsConfig)
 */

class TicketsApp {
  /** @param {Object} config */
  constructor(config = {}) {
    this.cfg = config || {};
    // DOM caches (lazily resolved)
    this.$ = (sel, root = document) => root.querySelector(sel);
    this.$$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    this.events = [];

    // Classname contracts / selectors reused throughout
    this.sel = {
      ticketForm: '#ticketForm',
      commentForm: '#comment-form',
      commentFormPh: '#comment-form-placeholder',
      commentsWrap: '#comments',
      comment: '.ticket-comment',
      commentMeta: '.ticket-comment .comment-reply a',
      commentTotal: '#comment-total, .ticket-comments-count',
      ticketMeta: '.ticket-meta',
      tpanelWrap: '#comments-tpanel',
      tpanelRefresh: '#tpanel-refresh',
      tpanelNew: '#tpanel-new',
      commentNewLink: '#comment-new-link',
      ticketEditor: '#ticket-editor',
      commentEditor: '#comment-editor',
      ticketPreviewPlaceholder: '#ticket-preview-placeholder',
      commentPreviewPlaceholder: '#comment-preview-placeholder',
      commentsContainer: '#comments',
      ticketFilesList: '#ticket-files-list',
    };

    // Toaster for messages
    this.toaster = new TicketsToaster();

    // basic init
    this.init();
  }

  /* ======================== INIT ======================== */
  init() {
    // Load optional libs if needed
    this.ensurePrettify();
    this.ensureSortable();

    // Bind UI
    this.bindGlobalEvents();

    // DOM ready equivalent
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.onReady());
    } else {
      this.onReady();
    }
  }

  onReady() {
    // Editors: keep plain textarea by default; hook point if you add a light editor
    // Counts
    this.updateCommentsCount();

    // Sisyphus-like autosave for ticket create form
    this.initSisyphus();

    // Auto hide new comment button if form is visible
    const cf = this.$(this.sel.commentForm);
    const newLink = this.$(this.sel.commentNewLink);
    if (cf && newLink) {
      if (this.isVisible(cf)) newLink.style.display = 'none';
    }

    // Init tpanel
    this.tpanel = new TicketsTpanel(this);
    this.tpanel.initialize();
  }

  /* ==================== EXTERNAL LIBS ==================== */
  ensurePrettify() {
    if (typeof window.prettyPrint !== 'function') {
      this.loadScript(this.cfg.jsUrl + 'lib/prettify/prettify.js', () => {
        if (typeof window.prettyPrint === 'function') {
          window.prettyPrint();
        }
      });
      this.loadCSS(this.cfg.jsUrl + 'lib/prettify/prettify.css');
    }
  }

  ensureSortable() {
    if (typeof window.Sortable !== 'function') {
      this.loadScript(this.cfg.jsUrl + 'lib/sortable/Sortable.min.js');
      // No jQuery binding needed
    }
  }

  loadScript(src, onload) {
    const s = document.createElement('script');
    s.src = src;
    s.async = true;
    if (onload) s.onload = onload;
    document.head.appendChild(s);
  }
  loadCSS(href) {
    const l = document.createElement('link');
    l.rel = 'stylesheet';
    l.type = 'text/css';
    l.href = href;
    document.head.appendChild(l);
  }

  /* ======================== HELPERS ======================= */
  isVisible(el) {
    return !!(el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length));
  }

  delegate(event, selector, handler) {
    const fn = (e) => {
      const t = e.target.closest(selector);
      if (t && e.currentTarget.contains(t)) handler(e, t);
    };
    document.addEventListener(event, fn);
    this.events.push({ event, fn });
  }

  post(action, bodyObj = {}, formData) {
    const url = this.cfg.actionUrl;
    let body;

    if (formData instanceof FormData) {
      body = formData;
      body.append('action', action);
    } else {
      body = new FormData();
      body.append('action', action);
      Object.entries(bodyObj || {}).forEach(([k, v]) => body.append(k, v));
    }

    return fetch(url, { method: 'POST', body }).then((r) => r.json());
  }

  dispatch(name, detail) {
    document.dispatchEvent(new CustomEvent(name, { detail }));
  }

  updateCommentsCount() {
    const count = this.$$(this.sel.comment).length;
    this.$$(this.sel.commentTotal).forEach((el) => (el.textContent = String(count)));
  }

  smoothGoto(id) {
    const el = document.getElementById(id);
    if (!el) return;
    window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY, behavior: 'smooth' });
  }

  enableButtons(root, enabled) {
    this.$$('[type="submit"], [type="button"]', root).forEach((b) => (b.disabled = !enabled));
  }

  setFieldError(form, field, message) {
    let elem = form.querySelector(`[name="${CSS.escape(field)}"]`);
    let holder = elem ? elem.closest('.field') || elem.parentElement : null;
    let err = holder ? holder.querySelector('.error') : null;
    if (!err && field) {
      err = form.querySelector('#' + field + '-error');
    }
    if (err) err.textContent = message || '';
  }

  clearErrors(form) {
    this.$$('.error', form).forEach((e) => (e.textContent = ''));
  }

  /* ===================== GLOBAL EVENTS ==================== */
  bindGlobalEvents() {
    // Prevent default for preview placeholder links
    this.delegate('click', `${this.sel.commentPreviewPlaceholder} a`, (e) => e.preventDefault());

    // Subscribe toggles
    this.delegate('change', '#comments-subscribe', () => {
      const thread = this.$(`${this.sel.commentForm} [name="thread"]`);
      this.commentSubscribe(thread);
    });
    this.delegate('change', '#tickets-subscribe', (e, el) => {
      const id = el.dataset.id;
      this.ticketSubscribe(id);
    });
    this.delegate('change', '#tickets-author-subscribe', (e, el) => {
      const id = el.dataset.id;
      this.authorSubscribe(id);
    });

    // Submit handlers
    document.addEventListener('submit', (e) => {
      const f = e.target;
      if (f.matches(this.sel.ticketForm)) {
        e.preventDefault();
        const submitBtn = f.querySelector('[type="submit"], [type="button"].save, [type="button"].publish, [type="button"].draft');
        this.ticketSave(f, submitBtn || null);
        return;
      }
      if (f.matches(this.sel.commentForm)) {
        e.preventDefault();
        const submitBtn = f.querySelector('[type="submit"], [type="button"].submit');
        this.commentSave(f, submitBtn || null);
        return;
      }
    });

    // Ticket: preview/save/draft/publish buttons
    this.delegate('click', '#ticketForm .preview, #ticketForm .save, #ticketForm .draft, #ticketForm .publish', (e, el) => {
      e.preventDefault();
      if (el.classList.contains('preview')) this.ticketPreview(el.form, el);
      else this.ticketSave(el.form, el);
    });

    // Ticket: delete/undelete
    this.delegate('click', '#ticketForm .delete, #ticketForm .undelete', (e, el) => {
      e.preventDefault();
      const confirmText = el.getAttribute('data-confirm') || 'Are you sure?';
      const action = el.classList.contains('delete') ? 'delete' : 'undelete';
      if (confirm(confirmText)) this.ticketDelete(el.form, el, action);
    });

    // Comment: preview/submit
    this.delegate('click', '#comment-form .preview, #comment-form .submit', (e, el) => {
      e.preventDefault();
      if (el.classList.contains('preview')) this.commentPreview(el.form, el);
      else this.commentSave(el.form, el);
    });

    // Hotkeys on forms (Enter + Ctrl/Cmd = preview; Shift+Enter+Ctrl/Cmd = submit)
    document.addEventListener('keydown', (e) => {
      const targetForm = e.target.closest('#ticketForm, #comment-form');
      if (!targetForm) return;
      if (e.key === 'Enter') {
        if (e.shiftKey && (e.ctrlKey || e.metaKey)) {
          e.preventDefault();
          targetForm.requestSubmit();
        } else if (e.ctrlKey || e.metaKey) {
          e.preventDefault();
          const previewBtn = targetForm.querySelector('input[type="button"].preview, .preview');
          if (previewBtn) previewBtn.click();
        }
      }
    });

    // Show/hide forms
    this.delegate('click', '#comment-new-link a', (e) => {
      e.preventDefault();
      this.formsComment(true);
    });
    // Reply/Edit links
    this.delegate('click', '.comment-reply a', (e, el) => {
      e.preventDefault();
      const parentComment = el.closest('.ticket-comment');
      const id = parentComment ? parentComment.dataset.id : null;
      if (!id) return;
      if (el.classList.contains('reply')) this.formsReply(id);
      else if (el.classList.contains('edit')) this.formsEdit(id);
    });

    // Votes & rating (comments)
    this.delegate('click', '.ticket-comment-rating.active > .vote', (e, el) => {
      e.preventDefault();
      const id = el.closest('.ticket-comment')?.dataset.id;
      if (!id) return;
      if (el.classList.contains('plus')) this.voteComment(el, id, 1);
      else if (el.classList.contains('minus')) this.voteComment(el, id, -1);
    });
    // Votes & rating (ticket)
    this.delegate('click', '.ticket-rating.active > .vote', (e, el) => {
      e.preventDefault();
      const id = el.closest('.ticket-meta')?.dataset.id;
      if (!id) return;
      if (el.classList.contains('plus')) this.voteTicket(el, id, 1);
      else if (el.classList.contains('minus')) this.voteTicket(el, id, -1);
      else this.voteTicket(el, id, 0);
    });

    // Stars
    this.delegate('click', '.ticket-comment-star.active > .star', (e, el) => {
      e.preventDefault();
      const id = el.closest('.ticket-comment')?.dataset.id;
      if (id) this.starComment(el, id, 0);
    });
    this.delegate('click', '.ticket-star.active > .star', (e, el) => {
      e.preventDefault();
      const id = el.closest('.ticket-meta')?.dataset.id;
      if (id) this.starTicket(el, id, 0);
    });

    // Link to parent comment
    this.delegate('click', '#comments .ticket-comment-up a', (e, el) => {
      e.preventDefault();
      const id = el.dataset.id;
      const parent = el.dataset.parent;
      if (parent && id) {
        this.smoothGoto('comment-' + parent);
        const down = this.$('#comment-' + parent + ' .ticket-comment-down');
        if (down) {
          down.style.display = '';
          const a = down.querySelector('a');
          if (a) a.dataset.child = id;
        }
      }
    });

    // Link to child comment
    this.delegate('click', '#comments .ticket-comment-down a', (e, el) => {
      e.preventDefault();
      const child = el.dataset.child;
      if (child) this.smoothGoto('comment-' + child);
      el.dataset.child = '';
      const p = el.parentElement;
      if (p) p.style.display = 'none';
    });
  }

  /* ======================= TICKET API ===================== */
  ticketPreview(form, button) {
    const fd = new FormData(form);
    fd.append('action', 'ticket/preview');
    if (button) button.disabled = true;
    return fetch(this.cfg.actionUrl, { method: 'POST', body: fd })
      .then((r) => r.json())
      .then((resp) => {
        this.dispatch('tickets_ticket_preview', resp);
        const el = this.$(this.sel.ticketPreviewPlaceholder);
        if (resp.success) {
          if (el) {
            el.innerHTML = resp.data.preview || '';
            el.style.display = '';
          }
          if (typeof window.prettyPrint === 'function') window.prettyPrint();
        } else {
          if (el) {
            el.innerHTML = '';
            el.style.display = 'none';
          }
          this.toaster.error(resp.message);
        }
      })
      .finally(() => {
        if (button) button.disabled = false;
      });
  }

  ticketDelete(form, button, action) {
    const fd = new FormData(form);
    fd.append('action', 'ticket/' + action);
    if (button) button.disabled = true;
    return fetch(this.cfg.actionUrl, { method: 'POST', body: fd })
      .then((r) => r.json())
      .then((resp) => {
        this.dispatch('tickets_ticket_' + action, resp);
        if (resp.success) {
          if (resp.message) this.toaster.success(resp.message);
          if (resp.data?.redirect) window.location.href = resp.data.redirect;
        } else {
          this.toaster.error(resp.message);
        }
      })
      .finally(() => {
        if (button) button.disabled = false;
      });
  }

  ticketSave(form, button) {
    let action = 'ticket/';
    if (button && button.name === 'draft') action += 'draft';
    else if (button && button.name === 'save') action += 'save';
    else action += 'publish';

    const fd = new FormData(form);
    fd.append('action', action);

    this.enableButtons(form, false);
    this.clearErrors(form);

    return fetch(this.cfg.actionUrl, { method: 'POST', body: fd })
      .then((r) => r.json())
      .then((resp) => {
        this.dispatch('tickets_ticket_save', resp);
        // release autosave on create form
        if (this.ticketSisyphus) this.ticketSisyphus.release();

        if (resp.success) {
          if (resp.message) this.toaster.success(resp.message);
          if (action === 'ticket/save') {
            this.enableButtons(form, true);
            if (resp.data?.content) {
              const ed = this.$(this.sel.ticketEditor);
              if (ed) ed.value = resp.data.content;
            }
            // remove deleted files
            const list = this.$(this.sel.ticketFilesList);
            if (list) this.$$('.deleted', list).forEach((n) => n.remove());
          } else if (resp.data?.redirect) {
            window.location.href = resp.data.redirect;
          }
        } else {
          this.enableButtons(form, true);
          this.toaster.error(resp.message);
          if (resp.data) {
            (resp.data || []).forEach((f) => this.setFieldError(form, f.field, f.message));
          }
        }
      })
      .catch((err) => this.toaster.error(err.message))
      .finally(() => {
        this.enableButtons(form, true);
      });
  }

  ticketSubscribe(sectionId) {
    if (!sectionId) return;
    this.post('section/subscribe', { section: sectionId }).then((resp) => {
      if (resp.success) this.toaster.success(resp.message);
      else this.toaster.error(resp.message);
    });
  }

  /* ====================== COMMENT API ===================== */
  commentPreview(form, button) {
    const fd = new FormData(form);
    fd.append('action', 'comment/preview');
    if (button) button.disabled = true;
    return fetch(this.cfg.actionUrl, { method: 'POST', body: fd })
      .then((r) => r.json())
      .then((resp) => {
        this.dispatch('tickets_comment_preview', resp);
        if (button) button.disabled = false;
        if (resp.success) {
          const holder = this.$(this.sel.commentPreviewPlaceholder);
          if (holder) {
            holder.innerHTML = resp.data.preview || '';
            holder.classList.add('active');
          }
          if (typeof window.prettyPrint === 'function') window.prettyPrint();
        } else {
          this.toaster.error(resp.message);
        }
      })
      .catch((err) => this.toaster.error(err.message))
      .finally(() => {
        if (button) button.disabled = false;
      });
  }

  commentSave(form, button) {
    const fd = new FormData(form);
    fd.append('action', 'comment/save');

    if (window.ticketsTimer) clearInterval(window.ticketsTimer);
    this.clearErrors(form);
    if (button) button.disabled = true;

    return fetch(this.cfg.actionUrl, { method: 'POST', body: fd })
      .then((r) => r.json())
      .then((resp) => {
        if (button) button.disabled = false;
        this.dispatch('tickets_comment_save', resp);

        if (resp.success) {
          this.formsComment(false);
          const prev = this.$(this.sel.commentPreviewPlaceholder);
          if (prev) prev.innerHTML = '', (prev.style.display = 'none');

          const ed = this.$(`${this.sel.commentForm} ${this.sel.commentEditor}`);
          if (ed) ed.value = '';
          this.$$(this.sel.commentMeta).forEach((a) => (a.style.display = ''));

          // autoPublish = 0 case
          if (!resp.data?.length && resp.message) {
            this.toaster.info(resp.message);
          } else if (resp.data?.comment) {
            this.commentInsert(resp.data.comment);
            const id = this.extractIdFromHTML(resp.data.comment);
            if (id) this.smoothGoto(id);
          }
          this.commentGetList();
          if (typeof window.prettyPrint === 'function') window.prettyPrint();
        } else {
          this.toaster.error(resp.message);
          if (resp.data) {
            const errors = [];
            (resp.data || []).forEach((f) => {
              const ok = this.setFieldError(form, f.field, f.message);
              if (!ok && f.field && f.message) errors.push(`${f.field}: ${f.message}`);
            });
            if (errors.length) this.toaster.error(errors.join('\n'));
          }
          if (resp.data?.captcha) {
            const c = form.querySelector('input[name="captcha"]');
            if (c) c.value = '', c.focus();
            const lbl = form.querySelector('#comment-captcha');
            if (lbl) lbl.textContent = resp.data.captcha;
          }
        }
      })
      .catch((err) => this.toaster.error(err.message))
      .finally(() => {
        if (button) button.disabled = false;
      });
  }

  commentGetList() {
    const form = this.$(this.sel.commentForm);
    if (!form) return false;
    const thread = form.querySelector('[name="thread"]');
    if (!thread) return false;

    if (this.tpanel) this.tpanel.start();
    this.post('comment/getlist', { thread: thread.value }).then((resp) => {
      Object.keys(resp.data?.comments || {}).forEach((k) => {
        this.commentInsert(resp.data.comments[k], true);
      });
      this.updateCommentsCount();
      if (this.tpanel) this.tpanel.stop();
    });
    return true;
  }

  commentInsert(htmlString, removeExisting) {
    // html string -> element
    const tpl = document.createElement('template');
    tpl.innerHTML = htmlString.trim();
    const comment = tpl.content.firstElementChild;
    if (!comment) return;

    const parent = comment.getAttribute('data-parent');
    const id = comment.getAttribute('id');
    const exists = id ? document.getElementById(id) : null;
    let childrenHTML = '';

    if (exists) {
      const np = exists.dataset.newparent;
      if (np) comment.setAttribute('data-newparent', np);
      if (removeExisting) {
        const cl = exists.querySelector('.comments-list');
        if (cl) childrenHTML = cl.innerHTML;
        exists.remove();
      } else {
        exists.replaceWith(comment);
        return;
      }
    }

    const commentsRoot = this.$(this.sel.commentsContainer);
    if (!commentsRoot) return;

    if (parent == '0' && this.cfg.formBefore) {
      commentsRoot.prepend(comment);
    } else if (parent == '0') {
      commentsRoot.append(comment);
    } else {
      let pcomm = document.getElementById('comment-' + parent);
      if (pcomm && pcomm.dataset.parent !== pcomm.dataset.newparent) {
        const np = pcomm.dataset.newparent;
        comment.setAttribute('data-newparent', np);
      } else if (this.cfg.thread_depth) {
        const level = pcomm ? pcomm.closestAll?.('.ticket-comment')?.length || this.depthOf(pcomm) : 0;
        if (level > 0 && level >= (this.cfg.thread_depth - 1)) {
          const newParent = pcomm ? pcomm.dataset.parent : parent;
          comment.setAttribute('data-newparent', newParent);
        }
      }
      const target = document.querySelector('#comment-' + parent + ' > .comments-list');
      if (target) target.append(comment);
    }

    if (childrenHTML) {
      const cl = document.getElementById(id)?.querySelector('.comments-list');
      if (cl) cl.innerHTML = childrenHTML;
    }
  }

  depthOf(node) {
    let d = 0, n = node;
    while (n && n.classList && n.classList.contains('ticket-comment')) {
      d++;
      n = n.parentElement?.closest('.ticket-comment');
    }
    return d - 1; // parent itself excluded
  }

  extractIdFromHTML(html) {
    const m = html.match(/id="([^"]+)"/);
    return m ? m[1] : null;
  }

  commentSubscribe(threadInput) {
    if (!threadInput) return;
    this.post('comment/subscribe', { thread: threadInput.value }).then((resp) => {
      if (resp.success) this.toaster.success(resp.message);
      else this.toaster.error(resp.message);
    });
  }

  /* ===================== AUTHOR API ====================== */
  authorSubscribe(authorId) {
    if (!authorId) return;
    this.post('author/subscribe', { author: authorId }).then((resp) => {
      if (resp.success) this.toaster.success(resp.message);
      else this.toaster.error(resp.message);
    });
  }

  /* ======================== FORMS ======================== */
  formsReply(commentId) {
    const newLink = this.$(this.sel.commentNewLink);
    if (newLink) newLink.style.display = '';

    if (window.ticketsTimer) clearInterval(window.ticketsTimer);

    const form = this.$(this.sel.commentForm);
    if (!form) return false;

    const time = form.querySelector('.time');
    if (time) time.textContent = '';

    this.$$(this.sel.commentMeta).forEach((a) => (a.style.display = ''));

    const prev = this.$(this.sel.commentPreviewPlaceholder);
    if (prev) prev.style.display = 'none';

    form.querySelector('input[name="parent"]').value = String(commentId);
    form.querySelector('input[name="id"]').value = '0';

    // files
    if (typeof window.Tickets !== 'undefined' && typeof window.Tickets.StartPlupload !== 'undefined') {
      const list = this.$('#ticket-files-list');
      if (list) this.$$('.ticket-file', list).forEach((n) => n.remove());
      window.Tickets.Uploader?.destroy?.();
      window.Tickets.StartPlupload();
    }

    const replyHolder = this.$('#comment-' + commentId + ' > .comment-reply');
    if (replyHolder) {
      replyHolder.insertAdjacentElement('afterend', form);
      form.style.display = '';
      const a = replyHolder.querySelector('a');
      if (a) a.style.display = 'none';
      replyHolder.closest('.ticket-comment')?.classList.remove('ticket-comment-new');
    }

    const ed = this.$(this.sel.commentEditor);
    if (ed) {
      ed.value = '';
      ed.focus();
    }
    return false;
  }

  formsComment(focus = true) {
    if (window.ticketsTimer) clearInterval(window.ticketsTimer);

    const newLink = this.$(this.sel.commentNewLink);
    if (newLink) newLink.style.display = 'none';

    const form = this.$(this.sel.commentForm);
    if (!form) return false;

    const time = form.querySelector('.time');
    if (time) time.textContent = '';

    this.$$(this.sel.commentMeta).forEach((a) => (a.style.display = ''));

    const prev = this.$(this.sel.commentPreviewPlaceholder);
    if (prev) prev.style.display = 'none';

    form.querySelector('input[name="parent"]').value = '0';
    form.querySelector('input[name="id"]').value = '0';

    if (typeof window.Tickets !== 'undefined' && typeof window.Tickets.StartPlupload !== 'undefined') {
      const list = this.$('#ticket-files-list');
      if (list) this.$$('.ticket-file', list).forEach((n) => n.remove());
      window.Tickets.Uploader?.destroy?.();
      window.Tickets.StartPlupload();
    }

    const ph = this.$(this.sel.commentFormPh);
    if (ph) ph.insertAdjacentElement('afterend', form);
    form.style.display = '';

    const ed = this.$(this.sel.commentEditor);
    if (ed) {
      ed.value = '';
      if (focus) ed.focus();
    }
    return false;
  }

  formsEdit(commentId) {
    const newLink = this.$(this.sel.commentNewLink);
    if (newLink) newLink.style.display = '';

    const thread = this.$(`${this.sel.commentForm} [name="thread"]`)?.value || '';
    const formKey = this.$(`${this.sel.commentForm} [name="form_key"]`)?.value || '';

    this.post('comment/get', { id: commentId, thread, form_key: formKey }).then((resp) => {
      if (!resp.success) {
        this.toaster.error(resp.message);
        return;
      }

      if (window.ticketsTimer) clearInterval(window.ticketsTimer);
      this.$$(this.sel.commentMeta).forEach((a) => (a.style.display = ''));

      const form = this.$(this.sel.commentForm);
      if (!form) return;

      const prev = this.$(this.sel.commentPreviewPlaceholder);
      if (prev) prev.style.display = 'none';

      form.querySelector('input[name="parent"]').value = '0';
      form.querySelector('input[name="id"]').value = String(commentId);

      if (typeof window.Tickets !== 'undefined' && typeof window.Tickets.StartPlupload !== 'undefined') {
        const filesWrap = this.$('.comment-form-files');
        if (filesWrap && resp.data?.files) filesWrap.innerHTML = resp.data.files;
        window.Tickets.Uploader?.destroy?.();
        window.Tickets.StartPlupload(commentId);
      }

      const reply = this.$('#comment-' + commentId + ' > .comment-reply');
      const timeLeft = form.querySelector('.time');
      if (timeLeft) timeLeft.textContent = '';
      if (reply) {
        reply.insertAdjacentElement('afterend', form);
        form.style.display = '';
        const a = reply.querySelector('a');
        if (a) a.style.display = 'none';
      }

      const ed = this.$(this.sel.commentEditor);
      if (ed) {
        ed.value = resp.data?.raw || '';
        ed.focus();
      }
      if (resp.data?.name) {
        const n = form.querySelector('[name="name"]');
        if (n) n.value = resp.data.name;
      }
      if (resp.data?.email) {
        const m = form.querySelector('[name="email"]');
        if (m) m.value = resp.data.email;
      }

      let t = resp.data?.time || 0;
      const tick = () => {
        if (t > 0) {
          t -= 1;
          if (timeLeft) timeLeft.textContent = TicketsUtils.timer(t);
        } else {
          clearInterval(window.ticketsTimer);
          if (timeLeft) timeLeft.textContent = '';
        }
      };
      clearInterval(window.ticketsTimer);
      window.ticketsTimer = setInterval(tick, 1000);
    });
    return false;
  }

  /* ===================== VOTE & STAR ===================== */
  voteComment(linkEl, id, value) {
    const parent = linkEl.parentElement;
    const rating = parent?.querySelector('.rating');
    if (!parent || parent.classList.contains('inactive')) return false;

    this.post('comment/vote', { id, value }).then((resp) => {
      if (resp.success) {
        linkEl.classList.add('voted');
        parent.classList.remove('active');
        parent.classList.add('inactive');
        if (rating) {
          rating.textContent = resp.data?.rating ?? '';
          rating.title = resp.data?.title ?? '';
          rating.classList.remove('positive', 'negative');
          if (resp.data?.status === 1) rating.classList.add('positive');
          else if (resp.data?.status === -1) rating.classList.add('negative');
        }
      } else this.toaster.error(resp.message);
    });
    return true;
  }

  voteTicket(linkEl, id, value) {
    const parent = linkEl.parentElement;
    const rating = parent?.querySelector('.rating');
    if (!parent || parent.classList.contains('inactive')) return false;

    this.post('ticket/vote', { id, value }).then((resp) => {
      if (resp.success) {
        linkEl.classList.add('voted');
        parent.classList.remove('active');
        parent.classList.add('inactive');
        if (rating) {
          rating.textContent = resp.data?.rating ?? '';
          rating.title = resp.data?.title ?? '';
          rating.classList.remove('positive', 'negative');
          if (resp.data?.status === 1) rating.classList.add('positive');
          else if (resp.data?.status === -1) rating.classList.add('negative');
        }
      } else this.toaster.error(resp.message);
    });
    return true;
  }

  starComment(linkEl, id) {
    this.post('comment/star', { id }).then((resp) => {
      if (resp.success) linkEl.classList.toggle('stared'), linkEl.classList.toggle('unstared');
      else this.toaster.error(resp.message);
    });
    return true;
  }

  starTicket(linkEl, id) {
    const countEl = linkEl.parentElement?.querySelector('.ticket-star-count');
    this.post('ticket/star', { id }).then((resp) => {
      if (resp.success) {
        linkEl.classList.toggle('stared');
        linkEl.classList.toggle('unstared');
        if (countEl && typeof resp.data?.stars !== 'undefined') countEl.textContent = resp.data.stars;
      } else this.toaster.error(resp.message);
    });
    return true;
  }

  /* =================== AUTOSAVE (SISYPHUS) =================== */
  initSisyphus() {
    const form = this.$('#ticketForm.create');
    if (!form) return;

    const key = 'tickets:sisyphus:ticketForm';
    const exclude = this.$$('#ticketForm .disable-sisyphus');

    const shouldStore = (el) => !exclude.some((ex) => ex === el || ex.contains(el));

    const save = () => {
      const data = {};
      this.$$('#ticketForm input, #ticketForm textarea, #ticketForm select').forEach((el) => {
        if (!el.name || !shouldStore(el)) return;
        if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
        data[el.name] = el.value;
      });
      try {
        localStorage.setItem(key, JSON.stringify(data));
      } catch {}
    };

    const restore = () => {
      try {
        const raw = localStorage.getItem(key);
        if (!raw) return;
        const data = JSON.parse(raw);
        Object.entries(data).forEach(([name, val]) => {
          const el = form.querySelector(`[name="${CSS.escape(name)}"]`);
          if (el) el.value = val;
        });
      } catch {}
    };

    const release = () => {
      try {
        localStorage.removeItem(key);
      } catch {}
    };

    form.addEventListener('input', save);
    restore();

    this.ticketSisyphus = { release };
  }
}

/* ========================= TPANEL ========================= */
class TicketsTpanel {
  /** @param {TicketsApp} app */
  constructor(app) {
    this.app = app;
    this.classNew = 'ticket-comment-new';
  }

  get wrap() { return this.app.$(this.app.sel.tpanelWrap); }
  get refresh() { return this.app.$(this.app.sel.tpanelRefresh); }
  get newBtn() { return this.app.$(this.app.sel.tpanelNew); }

  initialize() {
    if (!this.wrap) return;
    if (this.app.cfg.tpanel) {
      this.wrap.style.display = '';
      this.stop();
    }
    if (this.refresh) {
      this.refresh.addEventListener('click', () => {
        this.app.$$('.' + this.classNew).forEach((n) => n.classList.remove(this.classNew));
        this.app.commentGetList();
      });
    }
    if (this.newBtn) {
      this.newBtn.addEventListener('click', () => {
        const elem = this.app.$('.' + this.classNew);
        if (!elem) return;
        window.scrollTo({ top: elem.getBoundingClientRect().top + window.scrollY, behavior: 'smooth' });
        elem.classList.remove(this.classNew);
        const count = parseInt(this.newBtn.textContent || '0', 10);
        if (count > 1) this.newBtn.textContent = String(count - 1);
        else this.newBtn.textContent = '', (this.newBtn.style.display = 'none');
      });
    }
  }

  start() { if (this.refresh) this.refresh.classList.add('loading'); }

  stop() {
    const count = this.app.$$('.' + this.classNew).length;
    if (this.newBtn) {
      if (count > 0) {
        this.newBtn.textContent = String(count);
        this.newBtn.style.display = '';
      } else this.newBtn.style.display = 'none';
    }
    if (this.refresh) this.refresh.classList.remove('loading');
  }
}

/* ========================= TOASTER ======================== */
class TicketsToaster {
  constructor() {
    this.container = document.createElement('div');
    this.container.className = 'tickets-toaster';
    Object.assign(this.container.style, {
      position: 'fixed', right: '16px', top: '16px', zIndex: 9999,
      display: 'flex', flexDirection: 'column', gap: '8px'
    });
    document.addEventListener('DOMContentLoaded', () => document.body.appendChild(this.container));
    if (document.readyState !== 'loading') document.body.appendChild(this.container);
  }
  show(type, message, opts = {}) {
    if (!message) return;
    const item = document.createElement('div');
    item.className = `tickets-message ${type}`;
    Object.assign(item.style, {
      padding: '10px 12px', borderRadius: '8px', color: '#fff',
      boxShadow: '0 6px 20px rgba(0,0,0,.15)', maxWidth: '380px',
      font: '14px/1.4 system-ui, sans-serif'
    });
    const bg = type === 'success' ? '#16a34a' : type === 'error' ? '#dc2626' : '#4b5563';
    item.style.background = bg;
    item.textContent = message;
    this.container.appendChild(item);
    const ttl = opts.ttl || 3000;
    setTimeout(() => item.remove(), ttl);
  }
  success(msg, o) { this.show('success', msg, o); }
  error(msg, o) { this.show('error', msg, o); }
  info(msg, o) { this.show('info', msg, o); }
  closeAll() { this.container.innerHTML = ''; }
}

/* ========================== UTILS ========================= */
class TicketsUtils {
  static addzero(n) { return n < 10 ? '0' + n : String(n); }
  static timer(diff) {
    const days = Math.floor(diff / (60 * 60 * 24));
    const hours = Math.floor(diff / (60 * 60));
    const mins = Math.floor(diff / 60);
    const secs = Math.floor(diff);

    const hh = hours - days * 24;
    const mm = mins - hours * 60;
    const ss = secs - mins * 60;

    const res = [];
    if (hh > 0) res.push(this.addzero(hh));
    res.push(this.addzero(mm));
    res.push(this.addzero(ss));
    return res.join(':');
  }
}

// Polyfill closestAll depth (utility)
if (!Element.prototype.closestAll) {
  Element.prototype.closestAll = function (selector) {
    const arr = [];
    let el = this.closest(selector);
    while (el) {
      arr.push(el);
      el = el.parentElement?.closest(selector) || null;
    }
    return arr;
  };
}

// Bootstrap
if (typeof window !== 'undefined' && typeof window.TicketsConfig !== 'undefined') {
  window.TicketsApp = new TicketsApp(window.TicketsConfig);
}
