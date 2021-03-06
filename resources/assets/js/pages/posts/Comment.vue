<template>
  <div class="box" id="comment">
    <article class="media" v-if="authCheck">
      <figure class="media-left">
        <p class="image is-64x64">
          <img :src="authUser.avatar" :alt="authUser.name">
        </p>
      </figure>
      <div class="media-content">
        <div class="field">
          <p class="control">
            <textarea class="textarea"
              placeholder="Nhập bình luận, Ctrl + Enter để gửi..."
              :class="{ 'is-danger': form.errors.has('message') && !form.parent_id }"
              rows="2"
              @keyup.ctrl.enter.prevent="sendComment($event, true)">
            </textarea>
          </p>
          <p v-if="form.errors.has('message') && !form.parent_id" class="help is-danger">{{ form.errors.get('message') }}</p>
        </div>
      </div>
    </article>
    <article class="media" v-else>Vui lòng đăng nhập để bình luận!</article>
    <article class="media" v-for="(comment, index) in comments" :key="comment.id" :id="'comment-' + comment.id">
      <figure class="media-left">
        <p class="image is-64x64">
          <img :src="comment.user.avatar" :alt="comment.user.name">
        </p>
      </figure>
      <div class="media-content">
        <div class="content">
          <p>
            <strong>{{ comment.user.name }}</strong>
            <br>
            <markdown :text="comment.message"></markdown>
            <small>
              <template v-if="authCheck">
                <a @click="likeOrUnlikeComment(comment)" v-text="hasLike(comment) ? 'Bỏ thích' : 'Thích'">Thích</a> ·
                <a @click="reply(comment.id)">Trả  lời</a> ·
                <a v-if="comment.user_id == authUser.id">Sửa ·</a>
              </template>
              <a :class="{ 'has-text-danger': hasLike(comment) }">
                <span class="icon is-small"><i class="fa fa-heart"></i></span>
                <small>{{ comment.likes_count }}</small> ·
              </a>
              <timeago :since="comment.created_at" :autoUpdate="10"/>
            </small>
          </p>
        </div>

        <article class="media" v-for="(child, index) in comment.childs" :key="child.id" :id="'comment-' + child.id">
          <figure class="media-left">
            <p class="image is-48x48">
              <img :src="child.user.avatar" :alt="child.user.name">
            </p>
          </figure>
          <div class="media-content">
            <div class="content">
              <p>
                <strong>{{ child.user.name }}</strong>
                <br>
                <markdown :text="child.message"></markdown>
                <!-- <br> -->
                <small>
                  <template v-if="authCheck">
                    <a @click="likeOrUnlikeComment(child)" v-text="hasLike(child) ? 'Bỏ thích' : 'Thích'"></a> ·
                    <a @click="reply(comment.id)">Trả lời</a> ·
                    <a v-if="comment.user_id == authUser.id">Sửa ·</a>
                  </template>
                  <a :class="{ 'has-text-danger': hasLike(child) }">
                    <span class="icon is-small"><i class="fa fa-heart"></i></span>
                    <small>{{ child.likes_count }}</small> ·
                  </a>
                  <timeago :since="child.created_at" :autoUpdate="10"/>
                </small>
              </p>
            </div>
          </div>
          <div class="media-right" v-if="authCheck && authUser.id == child.user_id">
            <button class="delete" @click="deleteComment({ comment: child, index })"></button>
          </div>
        </article>

        <article class="media" v-if="authCheck && form.parent_id == comment.id">
          <figure class="media-left">
            <p class="image is-48x48">
              <img :src="authUser.avatar" :alt="authUser.name">
            </p>
          </figure>
          <div class="media-content">
            <div class="content">
              <div class="field">
                <label class="label">{{ authUser.name }}</label>
                <div class="control">
                  <input class="input" :class="{ 'is-danger': form.errors.has('message') }" type="text" placeholder="Nhập bình luận" @keyup.enter="sendComment">
                  <p class="help" :class="{ 'is-danger': form.errors.has('message') }">{{ form.errors.get('message') }}</p>
                </div>
              </div>
            </div>
          </div>
        </article>
      </div>
      <div class="media-right" v-if="authCheck && authUser.id == comment.user_id">
        <button class="delete" @click="deleteComment({ comment, index })"></button>
      </div>
    </article>

    <a class="has-text-centered has-text-primary" v-if="!is_last_page" @click="fetchComments({})">Xem thêm bình luận</a>
  </div>
</template>
<script>
  import axios from 'axios'
  import { mapGetters, mapActions, mapState } from 'vuex'
  import Form from 'vform'
  export default {
    data() {
      return {
        form: new Form({
          type: 'post',
          message: '',
          slug: this.$route.params.slug,
          parent_id: null,
        })
      }
    },
    computed: {
      ...mapGetters(['authCheck', 'authUser']),
      ...mapState('comment', ['comments', 'is_last_page', 'loading'])
    },
    methods: {
      ...mapActions('article', ['updateArticle']),
      ...mapActions('comment', ['deleteComment', 'fetchComments', 'addComment', 'likeOrUnlikeComment']),
      async sendComment(e, isNewComment = false) {
        let target = e.currentTarget

        if (isNewComment)
          this.form.parent_id = null

        this.form.message = target.value
        let { data: { data }} = await this.form.post(route('comment.store'))
        data.childs = []
        data.likes = []
        this.addComment(data)
        let comments_count = this.$store.state.article.article.comments_count
        this.updateArticle({ 'comments_count': comments_count + 1 })
        target.value = ''
      },
      reply(parent_id) {
        this.form.parent_id = parent_id
        this.form.errors.clear()
      },
      hasLike(comment) {
        return this.authCheck ? comment.likes.some(like => like.user_id == this.authUser.id) : false
      },
      scrollToHash() {
        const hash = this.$route.hash
        if (!hash) return
        let element = $(hash)
        if (!element.length) return
        let padding = + element.css('padding-top').replace('px', '')
        let height = $('.navbar-menu').height()

        $('html').animate({
          scrollTop: element.offset().top - height - padding,
        })
      }
    },
    watch: {
      data() {
        this.comments = this.data
      },
      $route() {
        setTimeout(this.scrollToHash, 500)
      }
    },
    mounted() {
      setTimeout(this.scrollToHash, 1000)
    }
  }
</script>
