<template>
  <div class="container">
    <div class="columns">
      <div class="is-three-quarters column">
        <h1 class="title is-spaced">
          <button class="button is-danger is-outlined" v-if="post.is_public === false">Bản nháp</button>
          {{ post.title }}
        </h1>
        <p class="content">{{ post.excerpt }}</p>
        <nav class="level is-full-tablet">
          <div class="level-left" v-if="post.slug">
            <router-link :to="{ name: 'post.category', params: { slug: post.category.slug }}" class="level-item">
              <span class="icon icon-small"><i class="fa fa-folder"></i></span>
              <small>{{ post.category.name }}</small>
            </router-link>
            <router-link
              class="level-item"
              :to="{ name: 'user.show', params: { username: post.author.username }}">
              <span class="icon icon-small"><i class="fa fa-user"></i></span>
              <small>{{ post.author.name }}</small>
            </router-link>
            <a class="level-item">
              <span class="icon icon-small"><i class="fa fa-clock-o"></i></span>
              <small>
                <timeago
                  :since="post.created_at"
                  :max-time="86400 * 365"
                  :autoUpdate="5">
                </timeago>
              </small>
            </a>
          </div>
          <div class="level-right">
            <a class="level-item">
              <span class="icon icon-small"><i class="fa fa-eye"></i></span>
              <small>10</small>
            </a>
            <router-link :to="{ name: 'post.show', params: { slug: post.slug }, hash: '#comment'}" class="level-item">
              <span class="icon icon-small"><i class="fa fa-comments"></i></span>
              <small>{{ post.comments_count }}</small>
            </router-link>
            <a class="level-item">
              <span class="icon is-small"><i class="fa fa-heart"></i></span>
              <small>{{ post.likes_count }}</small>
            </a>
            <a class="level-item">
              <b-tooltip label="Copy URL">
                <span class="icon is-small"><i class="fa fa-link"></i></span>
              </b-tooltip>
            </a>
            <b-dropdown hoverable class="level-item"
              v-if="authCheck && authUser.id == post.author.id">
              <a class="button is-primary is-small is-outlined" slot="trigger">
                <b-icon icon="pencil-square-o" pack="fa"></b-icon>
                Tùy chọn
                <b-icon icon="caret-down"></b-icon>
              </a>
              <b-dropdown-item has-link>
                <router-link :to="{ name: 'post.edit', params: { slug: post.slug }}">
                  <b-icon icon="edit"></b-icon>Chỉnh sửa
                </router-link>
              </b-dropdown-item>
              <b-dropdown-item @click="updateStatus">
                <b-icon :icon="post.is_public ? 'floppy-o' : 'send-o'"></b-icon>
                {{ post.is_public ? 'Lưu nháp' : 'Công khai '}}
              </b-dropdown-item>
              <b-dropdown-item separator></b-dropdown-item>
              <b-dropdown-item class="has-text-danger" @click="deletePost">
                <b-icon icon="trash"></b-icon>Xóa bài này
              </b-dropdown-item>
            </b-dropdown>
          </div>
        </nav>
        <hr>
        <div class="content has-text-justified">
          <markdown :text="post.content"></markdown>
        </div>
        <nav class="level">
          <div class="level-left">
            <b-tooltip :label="hasLiked ? 'Bỏ thích' : 'Yêu thích'" type="is-dark">
              <a class="button level-item is-outlined"
                :class="{ 'is-danger': hasLiked, 'is-primary': !hasLiked }"
                @click="authCheck ? likeOrUnlike('post') : null">
                <b-icon icon="heart"></b-icon>
                <small>{{ post.likes_count }} Thích</small>
              </a>
            </b-tooltip>
          </div>

          <div class="level-right">
            <b-taglist style="margin-top: 0.5em">
              <b-tag rounded v-for="tag in post.tags" :key="tag.slug" class="level-item">
                <router-link :to="{ name: 'tag.show', params: { slug: tag.slug }}">
                  {{ tag.name }}
                </router-link>
              </b-tag>
            </b-taglist>
          </div>
        </nav>
        <hr>
        <comment></comment>
      </div>
      <div class="column is-12-touch is-fixed-top">
        <section class="right-sidebar">
          <article class="media">
            <figure class="media-left">
              <p class="image is-64x64">
                <n-image :t="post.author.name"></n-image>
              </p>
            </figure>
            <div class="media-content">
              <div class="content">
                <p>
                  <strong>{{ post.author.name }}</strong>
                  <small>@{{ post.author.username }}</small>
                </p>
              </div>
            </div>
          </article>
          <!-- <button class="button is-small is-outlined is-primary"><i class="fa fa-plus-circle"></i>&nbsp;Follow</button> -->
        </section>
      </div>
    </div>
  </div>
</template>
<script>
import axios from 'axios'
import Comment from './Comment'
import { mapGetters, mapActions, mapState } from 'vuex'
export default {
  metaInfo() {
    return {
      title: this.post.title || 'Đang tải',
      meta: [
        { vmid: 'keywords', name: 'keywords', content: this.post.tags ? this.post.tags.map(tag => tag.name).join() : ''},
        { vmid: 'description', name: 'description', content: this.post.excerpt }
      ]
    }
  },
  created() {
    this.fetchArticle('post.show')
    this.fetchComments({ refresh: true })
  },
  computed: {
    ...mapGetters(['authUser', 'authCheck']),
    ...mapGetters('article', ['hasLiked']),
    ...mapState('article', { post: 'article' })
  },
  methods: {
    ...mapActions('article', ['fetchArticle', 'updateArticle', 'likeOrUnlike', 'deleteArticle']),
    ...mapActions('comment', ['fetchComments']),
    async updateStatus() {
      await axios.patch(route('post.status', this.post.slug), {
        value: !this.post.is_public
      })
      this.updateArticle({ is_public: !this.post.is_public })
      this.$snackbar.open({
        message: this.post.is_public ? 'Đã công khai bài viết' : 'Đã lưu nháp bài viết',
        queue: false
      })
    },
    deletePost() {
      this.$dialog.confirm({
        title: 'Xóa bài viết',
        message: 'Bạn có chắc muốn <b>xóa</b> bài viết này? Hành dộng này không thể hoàn tác.',
        confirmText: 'Xóa',
        type: 'is-danger',
        hasIcon: true,
        onConfirm: async () => {
          let slug = this.$route.params.slug
          await axios.delete(route('post.destroy', slug))
          this.$toast.open({
            message: 'Đã xóa bài viết thành công!',
            type: 'is-success'
          }),
          this.$router.go(-1)
        }
      })
    },
  },
  watch: {
    '$route' (to, from) {
      this.fetchArticle('post.show')
      this.fetchComments({ refresh: true })
    }
  },
  components: {
    Comment
  }
}
</script>
