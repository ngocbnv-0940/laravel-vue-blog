<template>
  <div class="container">
    <div class="columns">
      <div class="is-three-quarters column">
        <template v-if="loaded">
          <template v-if="posts.length">
            <post :post="post" v-for="post in posts" :key="post.slug"></post>
            <b-pagination
              :total="total"
              :current.sync="page"
              order="is-centered"
              @change="changePage"
              per-page="15">
            </b-pagination>
          </template>
          <p class="has-text-centered subtitle" v-else>Chưa có nội dung, hãy <router-link :to="{ name: 'post.create' }">đăng bài đầu tiên</router-link>!</p>
        </template>
      </div>
      <div class="column">
        <section class="right-sidebar">
          <article class="media">
            <figure class="media-left">
              <p class="image is-64x64">
                <n-image :t="user.name"></n-image>
              </p>
            </figure>
            <div class="media-content">
              <div class="content">
                <p>
                  <strong>{{ user.name }}</strong><br>
                  <small>@{{ user.username }}</small>
                </p>
              </div>
            </div>
          </article>
          <article class="media">
            <table class="table is-fullwidth">
              <tr>
                <th>Email</th>
                <td class="has-text-right">{{ user.email }}</td>
              </tr>
              <tr>
                <th>Bài viết</th>
                <td class="has-text-right">{{ user.posts_count }}</td>
              </tr>
              <tr>
                <th>Tham gia</th>
                <td class="has-text-right"><timeago :since="user.created_at"/></td>
              </tr>
            </table>
          </article>
        </section>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Post from '../posts/post'
export default {
  props: {
    params: {
      type: Object,
      required: false
    }
  },
  metaInfo () {
    return { title: 'User' }
  },
  data() {
    return {
      posts: [],
      user: {},
      page: +this.$route.params.page || 1,
      total: 0,
      loaded: false
    }
  },
  async created() {
    await this.getPosts()
    this.loaded = true
    this.$store.commit('setTitle', { title: this.user.name, subtitle: `@${this.user.username}` })
  },
  methods: {
    async getPosts() {
      const loading = this.$loading.open()
      try {
        let params = {
          page: this.page,
          user: this.$route.params.username,
          ...this.params
        }
        let { data } = await axios.get(route('user.show', params))

        if (data.posts.current_page <= data.posts.last_page) {
          this.posts = data.posts.data
          this.user = data.user
          this.total = data.posts.total
        } else {
          this.$toast.open({
            message: this.$t('error_alert_text'),
            type: 'is-danger'
          })
        }
      } catch(e) {}
      loading.close()
    },
    changePage(page) {
      if (page == 1)
        this.$router.push({ name: this.$route.name, params: { username: this.$route.params.username }})
      else
        this.$router.push({ name: this.$route.name, params: { username: this.$route.params.username, page: page }})
    },
  },
  watch: {
    '$route' (to, from) {
      this.page = +to.params.page
      if (to.params.page == undefined)
        this.page = 1
      this.getPosts()
    }
  },
  components: {
    Post
  }
}
</script>
